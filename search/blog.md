Solr and Japanese articles
==========================

Implementing a great search feature for an english website is already quite
a task. When you add accented characters like you have in french, things tend
to get messy. What about more exotic languages like Japanse and Chinese ?

In this post, I will start by explaining some ground rules and technique used
in search engines, feel free to skip them, and then dig deeper in the specific
topic of languages based on ideograms instead of the latin alphabet.

The configuration example will be given for the Solr search engine. What this
post will not be however, is an introduction on how to use Solr in your project :
no installation procedure and basic configuration.

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
become cumbersome to have a complete list of errors that way.

FOUND HOW YOU CAN DO THAT WITH SOLR

### Stemming (this should be h3)


### Language specific text types


One last thing that could help quickly improve your search results is the use of specific
text fields for each languages.

Luckily enough, a lot of people from around the world took the time to create specific
Solr fields for some of the most used languages. Those are configured to analyze your
texts taking into account the various specificities of the source language.

I greatly encourage you to use the text field that correspond to your articles !


Ideograms
---------

When we tried to implement a search engine for a multi lingual website where we had articles in
japanese, chinese and korean, despite not knowing those languages at all, we quickly remarked that
our search engine was performing really poorly. On some occasion it wasn't even returning an article
we specifically copied a word from.

We had to do a lot of research to understand what was happening, here is compiled what we found along
the way in the hope you won't have to go the same path as us !

BLA BL BLA