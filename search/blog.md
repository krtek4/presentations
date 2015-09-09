Search: Get past English
=========================

Implementing a great search feature for an English website is already quite
a task. When you add accented characters like you have in French, things tend
to get messy. What about more exotic languages like Japanese and Chinese?

When we tried to implement a search engine for a multi lingual website where we
had articles in Chinese, Japanese and Korean, despite not knowing those languages
at all, we quickly remarked that our search engine was performing really poorly.
On some occasion it wasn't even returning an article we specifically copied a word
from.

We had to do a lot of research to understand what was happening, here is a compilation
of what we found along the way in the hope you won't have to go the same path as us!

Since our project is using Solr, this post will concentrate on how to use the
described techniques with it. The version used is Solr 4.5 but this should work on
newer version and most of them will also work on Solr 3 with only minor adaptation.

At first, returning results to a search query can be seen as easy, but pretty
soon you realize that everyone has a different way of expressing things. You
can also encounter spelling mistakes, usage of synonyms, use of conjugated verbs, etc.

Hopefully, a lot of intelligent people have already resolved those common
issues, there often just a few keystrokes away.

Stop words
----------

A stop word is a work too common in the language to bring any meaningful addition
to the search. In English examples are "the", "that", "and", "or".

Those words usually appears in every texts and thus are not helping at all when
searching. You can easily find databases of stop words for various languages on
the Internet. Solr ships its own lists.

Usually you apply stop words both in the indexation and query processing. You can
easily do that with Solr by adding a filter to your analyzers:

	<filter class="solr.StopFilterFactory" ignoreCase="true" words="stopwords.txt" enablePositionIncrements="true" />

Depending on the particular field treated in your articles, you might want to add
stop words specific to your dataset.

Synonyms
--------

Not everyone use the same words to describe the same things, this is great for poetry,
but it is the bane of search engines. Luckily, like for stop words, you can find
ready to use synonyms list on the Internet and it is as easy to use them with Solr:

	<filter class="solr.SynonymFilterFactory" synonyms="synonyms.txt" ignoreCase="true" expand="true"/>

Contrary to stop words filtering however, usually you use synonyms filters only on the
query to avoid cluttering your indexation space.

I also encourage you to extends your synonyms database with words that could be specific
to your field.

Spelling mistakes
-----------------

You could use synonyms filtering to catch common spelling mistakes, but it would quickly
become cumbersome to have a complete list of errors that way. Most of modern search engine
integrates spelling mistakes correction in their core.

Solr also has a nifty feature that operate exactly like do "Did you mean" proposition you
can sometimes see on the Google search page. It uses some rules and the document corpus
to propose other query to your user.

Describing how to implement this with Solr is out of scope, but you can find documentation
in the official wiki:

* [Solr spell checking][1]
* [Solr MoreLikeThis][2]

[1]: https://cwiki.apache.org/confluence/display/solr/Spell+Checking
[2]: https://cwiki.apache.org/confluence/display/solr/MoreLikeThis

Stemming
--------

Words can be used in their singular or plural forms and verbs can be conjugated. This makes
the job of the search engine really difficult. For example, if your user is looking for
"How to cut a diamond" you probably want to propose him the article "Diamond cutting".

The words "How", "to" and "a" will already be considered has stop words, so no problems
here, however, you want to have a match for "cutting". This is where stemming comes into
play.

Stemming is the action of keeping only the relevant parts of each word (the stem), in
this case it means that in most scenarios "cutting" and "cut" can be considered as identical.
In french, you would consider "coupure", "coupe", "coupez" and "couper" as identical also.

Stemming is often activated on both indexation and query analysis. There is multiple stemming
filters in Solr some more aggressive than others, usually the SnowballPorterFilterFactory is
used for English:

	<filter class="solr.SnowballPorterFilterFactory" language="English"/>

But it may be too aggressive for other languages, this is why specific stemmers exists: 
`GermanLightStemFilterFactory`, `FrenchLightStemFilterFactory`, `JapaneseKatakanaStemFilterFactory`, ...

Phonetic matching
-----------------

In some cases, for example when searching proper nouns, people will write something that
is phonetically close but does not have the same spelling. There is a wide range of
algorithms that can be used in this case.

There is a dedicated documentation available: [Solr Phonetic Matching][4]

[4]: https://cwiki.apache.org/confluence/display/solr/Phonetic+Matching

Working with ideograms
----------------------

Once you leave the known ground of the latin alphabet and its related languages, things start
to get more complicated. There is a lot of differences between the way we commonly approach
search and text comprehension that no longer holds.

As a disclaimer, I am no japanese, chinese or korean speaker, so anything I say concerning
those languages is to be taken with a grain of salt, it is only what I could comprehend of all
my readings on the subject. If you can have access to someone knowledgeable about those, I can only
advise you to speak with them to better your configuration even further.

The first difficulty we stumbled upon is that there are not really words you can base your
search upon. There are no spaces in the sentences, only a chain of ideograms. Usually, search
engines separate sentences in words in order to apply the various techniques we saw earlier, this
is not possible with ideograms based languages.

The usual solution to this issue is to use n-grams.

### n-grams

A n-gram is a sequence of n items, in our case ideograms, from a text. Let's make this clear using an example.
Say we have the sentence "To be or not to be." and we want to create n-gram of words from it, the result will be:

* 2-gram or bigram: "to be", "be or", "or not", "not to", "to be"
* 3-gram or trigram: "to be or", "be or not", "or not to", "not to be".

Now apply this transformation not to words but ideograms and you can start using a search engine like
Solr to search your articles.

There is a generic filter in Solr to create n-grams:

	<filter class="solr.NGramFilterFactory" minGramSize="1" maxGramSize="4"/>

But in our case, Solr has a special tokenizer for CJK (Chinese, Japanese, Korean) languages:

	<tokenizer class="solr.CJKTokenizerFactory"/>

If you use this tokenizer, Solr will automatically creates bigrams without the use of a
specific filter for that.

Once you either applied the filter or the tokenizer to generate "words", you can now apply the other techniques
seen above to improve your search results.

It is important to note that n-gramming generates many terms per document and thus greatly increase the
size of the index, impacting the performances. Sadly, as far as we found out, there's no real other
alternatives for Chinese and Korean. Japanese documents can however be treated using morphological
analysis.

### Morphological analysis

Since a sentence in Japanese may use up to 4 different alphabets, simple n-gramming often changes
meaning and semantics. You can see the "[Japanese Linguistics in Lucene and Solr][5]" presentation for 
more details about those issues. Fortunately, Solr integrates a morphological analyzer for Japanese: Kuromoji[7].

Morphological analysis tries to separate Japanese in "words" that are meaningful for each sentence. It uses
a statistical model to do so and thus there could be errors, but in any case the result won't be worst than 
simple n-gramming.

Kuromoji is shipped as a tokenizer and a serie of filters, so you can use it as you would any other Solr
feature we saw earlier:

     <tokenizer class="solr.JapaneseTokenizerFactory" mode="search"/>
     <filter class="solr.JapaneseBaseFormFilterFactory"/>
     <filter class="solr.JapanesePartOfSpeechStopFilterFactory" tags="lang/stoptags_ja.txt" />

[5]: http://www.slideshare.net/lucenerevolution/japanese-linguistics-in-lucene-and-solr
[7]: http://www.atilika.org/

Language specific text types
----------------------------

We just described some of the techniques that can be used to improve search results. Some
of them you can use only with specific languages, others can be applied to all of them. There 
is also a lot of tuning that can be done based on each language specificity.

Luckily enough, a lot of people from around the world took the time to create specific
Solr fields for some of the most used languages. Those are tailored to cather to each
language peculiarities in the best way possible.

You can found those fields in the [Solr example schema][3]. I highly recommend that you first check
there is there is an already existing field for the language you have to index and start from that.

The example schema is pretty big but also a well of best practices and knowledge when it comes to Solr,
use it as much as possible. I promise you will gain a lot of time.

[3]: http://svn.apache.org/viewvc/lucene/dev/trunk/solr/example/example-DIH/solr/solr/conf/schema.xml?view=markup

Conclusion
----------

We just brushed the surface on what it is possible to do with Solr and we haven't even started to talk
about fine tuning.

Most of the filters presented in this post have parameters that you can tweak to ensure great results
for your users. It is also really important to not underestimate what a good knowledge of the specific
business vocabulary can do if used to craft synonyms and stop words lists.

To go further, I can recommend you to start with the dedicated page of the Solr documentation about analyzing 
specific languages which should be up to date with the last techniques you can use for a whole list of 
languages: [Solr Language Analysis][6].

In particular, have a look on the [Language-Specific Factories][8] part which list all the filters and tokenizer
that are specific for each languages.

As a parting note, the order of filters can greatly impact performances and results. Usually you put filters removing
words first (stop words filters for example), then normalizing ones and finally stemming. You can also apply some filters
only at query time, like explained in the "Synonyms" part.

I hope this post can help you provide great search results to your customer and if you have any advice and
techniques that you would like to share, please leave a comment!

[6]: https://cwiki.apache.org/confluence/display/solr/Language+Analysis
[8]: https://cwiki.apache.org/confluence/display/solr/Language+Analysis#LanguageAnalysis-Language-SpecificFactories