Git : Tips & Tricks
===================

Git is a really powerful tool but it can sometimes be hard to grasp some concepts and the way of doing some things is convoluted.

In the following post, I'll try to list a few tips and tricks to make your life easier.

Thanks to my colleagues and all the people on the Internet for those !

Ignore whitespace changes
-------------------------

We start with probably the most known one : ignore whitespace when doing a diff. Some of you may know that it suffice to add `?w=1` to the github changes URL to start ignore whitespaces, you can do exactly the same on the command line by adding the `-w` argument to git :

```
git diff -w
```

This will make code reviews a lot easier when you had to change the indentation of your code for example.

Delete whitespace changes
-------------------------

In the same vein as the previous tip, there is also a way to stage only non-whitespace changed by running the following command :

```
git diff -w --no-color | git apply --cached --ignore-whitespace
```

We first generate a `diff` without them, and disable colors to be on the safe side, and then `apply` this patch. After running this, you will have the changes staged and all the whitespace will be unstaged. You can clear them by running `git checkout -- .`.

If you want to can add this to your git aliases to have a handy command :

```
git config --global alias.addnw "!sh -c 'git diff -w --no-color \"$@\" | git apply --cached --ignore-whitespace' -"
```

Highlight word changes
----------------------

Github users will know that the exact change is highlighted there instead of just the whole line, this makes reviewing code easier. You can achieve the same thing on the command line multiple way.

The easier one is to simply use the `--word-diff=color` argument :

```
git diff --word-diff=color
```

You can even change the regex used to match words, for example, to add the comma as word separator, `--word-diff-regex="[^[:space:],]+"`.

There is also a bit more complicated way which leads to better result : using the the [diff-highlight](highlight) script shipped with git. The script is not available in all git installation, you may need to download it and put it somewhere on your hard drive. Once you have located the script, simply add the following to your configuration file :

```
git config --global core.pager '/path/to/diff-highlight | less'
```

This will activate it for all commands, if you want to limit it to some only, you can configure each pager individually : `pager.diff`, `pager.log`, `pager.show`, `pager.<cmd>`.

[highlight]: https://github.com/git/git/tree/master/contrib/diff-highlight

Quickly see all tags
--------------------

`git tag` lists all tags, but does not really display useful informations. You can do a lot better with :

```
git log --decorate --oneline --simplify-by-decoration
```

Or as an alias :

```
git config --global alias.releases 'log --decorate --oneline --simplify-by-decoration --all'
```

Graphical Log
-------------

`git log` does not really convey information about merges, this is where the `--graph` attribute is coming handy. With some more tuning, it becomes this alias :

```
git config --global alias.graph 'log --graph --oneline --decorate --date-order --color --boundary --all'
```

This alias is just a start, you can configure the output a lot more. For example see this StackOverflow answer : [Pretty git branch graphs](so-graph)

[so-graph]: http://stackoverflow.com/questions/1057564/pretty-git-branch-graphs#answer-9074343

Clean merged branches
---------------------

In most git workflow, you end up creating a lot of branches. Once they are merged in your master branch, they usually aren't useful anymore. You can easily list all branches merged in the current branch :

```
git branch --merged  # local branches
git branch -r --merged   # remote branches
```

Here's how you can delete them :

```
# local branches
git branch --merged | grep -v "^\*" | xargs -n 1 git branch -d
# remote branches
git branch -r --merged | grep -v $(git rev-parse --abbrev-ref HEAD) | sed 's/origin\///' | xargs -n 1 git push --delete origin
```

Be careful running those. You can just display the branched that will be deleted by removing the last `|` and everything after.

Once you've cleaned remote branches, you should advise everyone including you to run the following to cleanup any renmants of those branches :

git remote prune origin

If you want more tips about git spring cleaning head to [Git housekeeping tutorial](spring)

[spring]: http://railsware.com/blog/2014/08/11/git-housekeeping-tutorial-clean-up-outdated-branches-in-local-and-remote-repositories/

Package of your repository
--------------------------

You can easily create archives of your repository with the `archive` command :

```
git archive -output=my_archive.zip master  # zip version
git archive master | bzip2 > my_archive.tar.bz2# tar.bz2 version
```

What is a little less known, is that `archive` will respect the `export-ignore` attribute in your `.gitattributes` file.

As an added bonus, some packaging tools, like Composer, will also ignore those files when creating the package thus saving bandwidth and disk space for everyone.

Add only part of the changes
----------------------------

How many times are you ready to commit some code only to realize that it would make a lot more sense if you could do it in multiple commits. It's pretty easy to do when you just want to separate changes made to different files, but you can also easily stage only part of the modifications done to a single file by using the `-p` switch.

When running `git add -p`, git will ask you what to do with each "hunk" (or code part). It will interactively ask you what to do for each group of changes it detects in a file. At any time you can issue the `?` to see all possible answers, the most used being :

* `y` to stage this modification
* `n` to keep this modification in the working copy
* `s` to split the modification in smaller parts

Ignore modifications to a committed file
----------------------------------------

It happens more often than not that you need to do some modifications on a file that you don't want committed. It maybe to change some configuration specific to your own environment or simply some debug code you use often.

If the file you've modified is already committed, `.gitignore` will not help you and the modification will always show when running `status` or `diff` and you have the risk of accidentally commit it.

You can use the `update-index` command to tell git to ignore changes made to a certain file like this :

```
git update-index --assume-unchanged path/to/my/file
```

Be aware than any further change to this file will also be ignored by git. If you want to revert the "assume unchanged" status, simply run :

```
git update-index --no-assume-unchanged path/to/my/file
```

Diff contextual information
---------------------------

Depending on the language you use, you eventually already saw that git tries to give you context information for each modification when doing a diff. This is called a hunk header and you can modify it to suites your need.

Say we want to modifiy how PHP hunks are contextualized. First we need to specify the diff driver to use for PHP files in your `.gitattributes` file :

```
 *.php   diff=php
```

For PHP this should suffice, because git already ships a corresponding diff driver. However, if you want to change the header anyway or add a new definition, add this in your `.gitconfig` :

```
[diff "phpf"]
xfuncname = "<regex for my hunk>"
```

Git will then go backward from the modification and display the first line that matches the regex you configured. For more details, you can head to the [official gitattributes documentation](gitattributes).

[gitattributes]: http://git-scm.com/docs/gitattributes#_defining_a_custom_hunk_header

Easy commit fixup
-----------------

When working on a feature, it happens that you want to do a commit that fixes something you commited earlier. Sure you can simply commit and then do some rebase magic, but what if I told you there's a better way ?

Say my history looks like this :

```
$ git log --oneline --decorate
xxxxxx3 (HEAD, feature-branch) third commit
xxxxxx2 second commit
xxxxxx1 first commit
xxxxxx0 (master) starting point
```

If I want to fix something introduced in the second commit I can simply do :

```
$ git add .
$ git commit --fixup xxxxxx2
$ git log --oneline --decorate
xxxxxx4 (HEAD, feature-branch) fixup! second commit
xxxxxx3 third commit
xxxxxx2 second commit
xxxxxx1 first commit
xxxxxx0 (master) starting point
```

Notice the `fixup!` prefix of the commit message ? Now git can use this information to automatically rebase your changes :

```
$ git rebase --interactive --autosquash master
```

This will open an interactive rebase editor session like you are accustomed to, **but** your fixup commit will be automatically placed correctly in the list with the correct action :

```
pick xxxxxx1 first commit
pick xxxxxx2 second commit
fixup xxxxxx4 fixup! second commit
pick xxxxxx3 third commit
```

Usually, it's now only a matter of accepting the rebase as is and tada !

If you want to aleays `autosquash` you can simply add it to your config :

```
git config --global rebase.autosquash true
```

I also defined some aliases to help :

```
fix= commit --fixup HEAD~1  # fixup the last commmit
fixup  = commit --fixup # fixup a commit in the history (need a revision)
squash = commit --squash# squash a commit in the history (need a revision)
ri = rebase --interactive   # interactive rebase
```

rerere : Reuse Recorded Resolution
----------------------------------

`rerere` is a feature of git that automatically records conflict resolution upon merges and is then able to reapply them automatically when the same conflicts arise again.

I personnaly didn't find any usecase in my day to day job justifying the use of `rerere`, but the following Medium article can be of help to people having sharing the same workflow as the other : [Fix conflicts only once with git rerere](medium-rerere)

[medium-rerere]: https://medium.com/@porteneuve/fix-conflicts-only-once-with-git-rerere-7d116b2cec67#.ahscsxcyu

The reflog : never loose a commit again
---------------------------------------

History modification is great in some cases but can also lead to loosing some commits on your branches. Fear not, you can retrieve them.

The `git reflog` command displays all action that were made on the working copy including all commits. Just find the lost commit in the list, copy its hash and then reapply it where ever you want by using the `cherry-pick` command.

`reflog` is also a great command to use when you are wondering what the heck you could have done today ;)

Tig
---

Tig is a console Git browser a bit like the GitHub client or Sourcetree but that works in any terminal. It is a great tool if you don't like the command line but still have to sometimes work over SSH on some servers.

Describing the features would be out of scope from this blog post, so I'll just link you to the homepage : [Tig](tig).

[tig]: https://github.com/jonas/tig

Autocorrect commands
--------------------

If you often do typos when writing git commands, just do :

```
$ git config --global help.autocorrect 1
```

Now git can autocorrect your errors for you :

```
$ git sttaus
WARNING: You called a Git command named 'sttaus', which does not exist.
Continuing under the assumption that you meant 'status'
in 0.1 seconds automatically...
```


Only blame on certain things
----------------------------

Sometimes a coworker reindent some code or move some lines around or to a completely different files. When trying to understand the origin of some lines those kind of changes get of the way of using `blame` efficiently. Fortunately, git is so powerful that even that can't stop you :

```
$ git blame -w  # ignore commits that only change white spaces
$ git blame -M  # ignore commits that moved the lines in the same file
$ git blame -C  # ignore commits that moved the lines from another file
```

Be aware that this renders the command slower however.

Add comments to commmit
-----------------------

Even wanted to add some comments to a commit ? It is possible with Git.

However, the feature is not really usable, but it may be come in handy some times so here's the link to a nice blog post explaining everything : [Note to Self](git-notes).

[git-notes]: http://git-scm.com/blog/2010/08/25/notes.html


Final words
-----------

Those are the tips and tricks I learned during the years I've been using Git. Some I use daily, some I just keep around for when the need arise. I hope those will be useful to you !

If you find the list lacking, please feel free to add your own tips in the comments and I'll update the post with them !

You can also have a look at the following ressources :

* [Github's Git tips](https://github.com/git-tips/tips)
* [Git tips for everyday use](http://www.alexkras.com/19-git-tips-for-everyday-use/)
* Git tips and tricks [part 1](http://hugogiraudel.com/2014/03/17/git-tips-and-tricks-part-1/) and [part 2](http://hugogiraudel.com/2014/03/17/git-tips-and-tricks-part-2/)
* [Git tips from the trenches](https://ochronus.com/git-tips-from-the-trenches/)
