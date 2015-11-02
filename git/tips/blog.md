Git : Tips & Tricks
-------------------

Git is a really powerful tool but it can sometimes be hard to grasp some concepts and the way of doing some things is convulated.

In the following post, I'll try to list a few tips and tricks to make your life easier.

Thanks to my colleagues and all the people on the Internet for those !

Ignore whitespace changes
=========================

We start with probably the most known one : ignore whitespace when doing a diff. Some of you may know that it suffice to add `?w=1` to the github changes URL to start ignore whitespaces, you can do exactly the same on the commande line by adding the `-w` argument to git :

    git diff -w

This will make code reviews a lot easier when you had to change the indentation of your code for example.

Delete whitespace changes
=========================

In the same vein as the previous tip, there is also a way to stage only non-whitespace changed by running the following command :

    git diff -w --no-color | git apply --cached --ignore-whitespace

We first generate a `diff` without them, and disable colors to be on the safe side, and then `apply` this patch. After running this, you will have the changes staged and all the whitespace will be unstaged. You can clear them by running `git checkout -- .`.

If you want to can add this to your git aliases to have a handy command :

    git config --global alias.addnw "!sh -c 'git diff -w --no-color \"$@\" | git apply --cached --ignore-whitespace' -"

Highlight word changes
======================

Github users will know that the exact change is highlighted there instead of just the whole line, this makes reviewing code easier. You can achieve the same thing on the command line multiple way.

The easier one is to simply use the `--word-diff=color` argument :

    git diff --word-diff=color

You can even change the regex used to match words, for example, to add the comma as word separator, `--word-diff-regex="[^[:space:],]+"`.

There is also a bit more complicated way which leads to better result : using the the (diff-highlight)[highlight] script shipped with git. The script is not available in all git installation, you may need to download it and put it somewhere on your hard drive. Once you have located the script, simply add the following to your configuration file :

    git config --global core.pager '/path/to/diff-highlight | less'

This will activate it for all commands, if you want to limit it to some only, you can configure each pager individually : `pager.diff`, `pager.log`, `pager.show`, `pager.<cmd>`.

[highlight]: https://github.com/git/git/tree/master/contrib/diff-highlight

Quickly see all tags
====================

`git tag` lists all tags, but does not really display useful informations. You can do a lot better with :

    git log --decorate --oneline --simplify-by-decoration

Or as an alias :

    git config --global alias.releases 'log --decorate --oneline --simplify-by-decoration --all'

Graphical Log
=============

`git log` does not really convey information about merges, this is where the `--graph` attribute is coming handy. With some more tuning, it becomes this alias :

   git config --global alias.graph 'log --graph --oneline --decorate --date-order --color --boundary --all'

This alias is just a start, you can configure the output a lot more. For example see this StackOverflow answer : (Pretty git branch graphs)[so-graph]

[so-graph]: http://stackoverflow.com/questions/1057564/pretty-git-branch-graphs#answer-9074343

Clean merged branches
=====================

In most git workflow, you end up creating a lot of branches. Once they are merged in your master branch, they usually aren't useful anymore. You can easily list all branches merged in the current branch :

    git branch --merged      # local branches
    git branch -r --merged   # remote branches

Here's how you can delete them :

    # local branches
    git branch --merged | grep -v "^\*" | xargs -n 1 git branch -d
    # remote branches
    git branch -r --merged | grep -v $(git rev-parse --abbrev-ref HEAD) | sed 's/origin\///' | xargs -n 1 git push --delete origin

Be careful running those. You can just display the branched that will be deleted by removing the last `|` and everything after.

Once you've cleaned remote branches, you should advise everyone including you to run the following to cleanup any renmants of those branches :

    git remote prune origin

If you want more tips about git spring cleaning head to (Git housekeeping tutorial)[spring]

[spring]: http://railsware.com/blog/2014/08/11/git-housekeeping-tutorial-clean-up-outdated-branches-in-local-and-remote-repositories/

Package of your repository
==========================

You can easily create archives of your repository with the `archive` command :

    git archive -output=my_archive.zip master          # zip version
    git archive master | bzip2 > my_archive.tar.bz2    # tar.bz2 version

What is a little less known, is that `archive` will respect the `export-ignore` attribute in your `.gitattributes` file.

As an added bonus, some packaging tools, like Composer, will also ignore those files when creating the package thus saving bandwidth and disk space for everyone.

Ignore modifications to a commited file
=======================================

Diff contextual information
===========================

Easy commit fixup
=================

rerere
======

The reflog : never loose a commit again
=======================================

Tig
===

Autocorrect commands
====================

https://ochronus.com/git-tips-from-the-trenches/

Add comments to commmit
=======================

http://git-scm.com/blog/2010/08/25/notes.html


Only blame on certain things
============================


See those too :

* https://github.com/git-tips/tips
* http://www.alexkras.com/19-git-tips-for-everyday-use/
* http://hugogiraudel.com/2014/03/17/git-tips-and-tricks-part-2/
