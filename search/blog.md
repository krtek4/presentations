Solr and Japanese articles
==========================

Implementing a great search feature for an english website is already quite
a task. When you add accented characters like you have in french, things tend
to get messy. What about more exotic languages like Japanse and Chinese ?

When we tried to implement a search engine for a multi lingual website where we
had articles in japanese, chinese and korean, despite not knowing those languages
at all, we quickly remarked that our search engine was performing really poorly.
On some occasion it wasn't even returning an article we specifically copied a word
from.

We had to do a lot of research to understand what was happening, here is a compilation
of what we found along the way in the hope you won't have to go the same path as us !

But before digging right in, let's start with some ground rules and technique used
in search engines, feel free to skip them.

The configuration example will be given for the Solr search engine. What this
post will not be however, is an introduction on how to use Solr in your project :
no installation procedure and basic configuration. The version used is the latest
available at the time this article was written : Solr 4.0.6

Search 101
----------

At first, returning results to a search query can be seen as easy, but pretty
soon you realize that everyone has a different way of expressing things. You
can also encounter spelling mistakes, usage of synonyms, use of conjugated verbs, etc.

Heureusement (???), a lot of intelligent people have already resolved those common
issues, there often just a few keystrokes away.

### Stop words

A stop word is a work too common in the language to bring any meaningful addition
to the search. In english examples are "the", "that", "and", "or".

Those words usually appears in every texts and thus are not helping at all when
searching. You can easily find databases of stopwords for various languages on
the internet. Solr ships its own (really ? check that !).

Usually you apply stop words both in the indexation and query processing. You can
easily do that with Solr by adding a filter to your analyzers :

	<filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords.txt" enablePositionIncrements="true" />

Depending on the particular field treated in your articles, you might want to add
stop words specific to your dataset.

### Synonyms

Not everyone use the same words to describe the same things, this is great for poetry,
but it is the bane of search engines. Luckily, like for stopwords, you can find
ready to use synonyms list on the internet and it is as easy to use them with Solr :

	<filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>

Contrary to stop words filtering however, usually you use synonyms filters only on the
query to avoid cluttering your indexation space.

I also encourage you to extends your synonyms database with words that could be specific
to your field.

### Spelling mistakes


You could use synonyms filtering to catch common spelling mistakes, but it would quickly
become cumbersome to have a complete list of errors that way. Most of modern search engine
spelling mistakes correction in their core.

	TODO: find how to do spelling mistake correction with Solr

Solr also has a nifty feature that operate exactly like do "Did you mean" proposition you
can sometimes see on the Google search page. It uses some rules and the document corpus
to propose other query to your user.

	TODO: find how to implement did you mean with Solr

### Stemming

Words can be used in their singular or plural forms and verbs can be conjugated. This makes
the job of the search engine really difficult. For example, if your user is looking for
"How to cut a diamond" you probably want to propose him the article "Diamond cutting".

The words "How", "to" and "a" will already be considered has stop words, so no problems
here, however, you want to have a match for "cutting". This is where stemming comes into
play.

Stemming is the action of keeping only the relevant parts of each word, in this case it means
that in most scenarie "cutting" and "cut" can be considered as identical. In french, you would
consider "coupe", "coupez" and "couper" as identical also.

Stemming is often activated on both indexation and query analysis, you can do it like that :

	TODO: find how to activate stemming with Solr


### Language specific text types


One last thing that could help quickly improve your search results is the use of specific
text fields for each languages.

Luckily enough, a lot of people from around the world took the time to create specific
Solr fields for some of the most used languages. Those are configured to analyze your
texts taking into account the various specificities of the source language.

The common way to use them is to declare various fields for each languages you are interested
in and the copy the searchable text for each document in those. You can then select
the right field to use based on the current locale of your application.

I greatly encourage you to use the text field that correspond to your articles !


Ideograms
---------

Once you leave the known ground of the latin alphabet and its related languages, things start
to get more complicated. There is a lot of differences between the way we commonly approach
search and text comprehension that no longer holds.

As a disclaimer, I am no japanese, chinese or korean speaker, so anything I say concerning
those languages is to be taken with a grain of salt, it is only what I could comprehend of all
my readings on the subject. If you can have access to someone knowledgeable about those, I can only
advise you to speak with them to better your configuration even further.

### n-gramms

The first difficulty we stumbled upon is that there are not really words you can base your
search upon. There are no spaces in the sentences, only a chain of ideograms. Usually, search
engines separate sentences in words in order to apply the various techniques we saw earlier, this
is not possible with ideograms based languages.

Luckily, some clever people came up with a solution, using n-gramms. TODO: explain this shit !


### Morphological analysis