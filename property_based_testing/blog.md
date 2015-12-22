Property Based testing : QuickCheck and co
==========================================

Around the same time last year, I made a presentation of [php-quickcheck][php-quickcheck] to my colleagues. This nifty library allows you to quickly test your functions with thousands of generated inputs to easily find bugs in your software by using a technique called property based testing.

The main advantage is that you don't have to think of test values anymore, just write a generator and let the library do all the work. This way you can concentrate on describing the features of your application instead of loosing time writing tests. It is also a great tool to find edge cases you've never even dreamed off.

At the end of the presentation, I promised them I'll write a blog post about it, and since it's never too late, here it is. Sorry for the time it took me.

[php-quickcheck]: https://github.com/steos/php-quickcheck

QuickCheck : a quick introduction
---------------------------------

Nearly 15 years ago, Jon Hugues and Koen Claessen, from Haskell fame, described and formalized a novel way to do testing. Instead of classical unit testing where the developer has to write assertion for various possible parameters to a function, they decided to write a tool allowing to describe *properties* of the tested function. The tool then try to feed various random values as parameter to the function and test if the properties hold.

The [original paper][2] probably describes everything better than I could ever do, but in short, the idea is to define two things :

* A way to define properties for the tested function
* A way to define generators for random values

A property is basically a predicate that must hold for every possible values, usually testing that the function return something meaningful is sufficient.

A generator is a function creating random values for a given parameters. Generators already exists for basic types (integer, strings, boolean), but to give maximum flexibility to the tester, he can define its own.

Then, you simply let QuickCheck do the grunt work, it will call the function multiple times, be it a hundred or a thousand, with random values and report any time the property failed to verify.

One other great feature of QuickCheck is that, once he finds a value that failed to validate the property, it tries to reduce the input to the simplest failing case. For example, if you test a sorting function, instead of providing you with the first buggy array it finds, it will try to find the smallest array that produce a wrong result.

This approach allows to more clearly think about what the system is supposed to do instead of focusing on finding test values. It can also fairly easily discover issues with edge cases that are really difficult to think about.

[2]: http://www.eecs.northwestern.edu/~robby/courses/395-495-2009-fall/quick.pdf

Fuzzing
-------

In a way, you could compare QuickCheck to fuzzing individual function.

For those not familiar with the term, [fuzzing][fuzzing] is the idea of feeding random data as input to a program to see how it reacts. It is often used on native application to discover memory leaks or possible security attack vectors.

You can also use fuzzing on web apps, for example to detect if user inputs are correctly sanitized. OWASP has a page about using fuzzing this way : [https://www.owasp.org/index.php/Fuzzing][owasp]

There are however two main differences between the two techniques :

* Fuzzing considers the program as a black box whereas QuickCheck try to validate a list of known properties
* Fuzzing performs on the program level whereas QuickCheck is used on individual functions.

In other words, Fuzzing is to functional testing what QuickCheck is to unit testing.

[fuzzing]: http://en.wikipedia.org/wiki/Fuzz_testing
[owasp]: https://www.owasp.org/index.php/Fuzzing

What about PHP ?
----------------

You can guess that this idea interested a lot of people. Implementation of QuickCheck exists in more than two dozen languages. PHP is no exception, over the time there were multiple tries to develop the concept in PHP. Recently, one of the attempt caught my eye : [php-quickcheck][1].

The core of the library may be a bit hard to grasp because it uses lot a functional technique to be as close as possible to the original. However, it is the first PHP version that includes the automagical input shrinking I discussed earlier.

In order to avoid losing too much time explaining, here is a quick example :

```
// predicate function that checks if the given array elements are in ascending order
function isAscending(array $xs) {
    $last = count($xs) - 1;
    for ($i = 0; $i < $last; ++$i) {
        if ($xs[$i] > $xs[$i + 1]) {
            return false;
        }
    }
    return true;
}

// sort function that is obviously broken
function myBrokenSort(array $xs) {
    return $xs;
}

// so let's test our sort function, it should work on all possible int arrays
$brokenSort = Gen::forAll(
    [Gen::ints()->intoArrays()],
    function(array $xs) {
        return isAscending(myBrokenSort($xs));
    }
);

var_dump(Quick::check(100, $brokenSort, ['echo' => true]));
```

The interesting part is the last one, where we use QuickCheck to test our previously defined sorting function. Our property states that for all (`Gen::forAll`) integer arrays (`Gen::ints()->intoArrays()`) the result of `myBrokenSort` should be ascending. Seeing the implementation of the function, it should be fairly clear that it won't be the case, but let's see what the output is :

```
{
    "result": false,
    "seed": 1411398418957,
    "failing_size": 6,
    "num_tests": 7,
    "fail": [
        [-3,6,5,-5,1]
    ],
    "shrunk": {
        "nodes_visited": 24,
        "depth": 7,
        "result": false,
        "smallest": [
            [1,0]
        ]
    }
}
```

This tells us that QuickCheck found an array falsifying the predicate after 7 tests, the original test value is `[-3,6,5,-5,1]`. Then, it successfully tried to shrunk it, the minimal case that invalidate the property is : `[1,0]`, which seems about right.

The library currently ship with generators for most of the scalar types. You also have a lot of methods to combine multiple generators and create new values from them. I'll let you have a look at the [README][5] for more details.

[5]: http://github.com/steos/php-quickcheck/README

### Not everything is great, at least for now

Sadly, the php-quickcheck library is still in its infancy and besides the generators and the check mechanism, nothing is really available. If you want to test multiple properties on a function, you will have to write a lot of boilerplate code.

A way to interact nicely with PHPUnit is currently in discussion, but sadly it is not that easy as PHPUnit evaluates each data set before running the tests suites, thus generating a lot of in memory data and considerably slowing the bootstrap process.

Any contribution, or simply ideas and suggestions are more than welcome on the project [bug tracker][6] !

[6]: http://github.com/steos/php-quickcheck/issues


Say I am using another language ?
---------------------------------

This article is just a quick presentation of property based testing, so a full tutorial about multiple implementation would be out of scope, here is however a quick list of QuickCheck implementation in other languages.

I don't guarantee those are the best implementation in each language, it is just a list of tools I've heard about provided to you without any kind of insurance ;)

* Python : [Hypothesis][hypothesis].
* Clojure : [test.check][test.check].
* Javascript : [JSVerify][JSVerify].
* Ruby : [rubycheck][rubycheck].

[hypothesis]: https://github.com/DRMacIver/hypothesis
[test.check]: https://github.com/clojure/test.check
[JSVerify]: https://github.com/jsverify/jsverify
[rubycheck]: https://github.com/mcandre/rubycheck

UI Testing
----------

Still not convinced ? What if I told you that you can even test your UI components using Property based testing ?

Ok, right now it's a bit of a stretch and it's probably only possible with [Om Next][omnext] and you can only test the actions and not how things are displayed.

Without diving too much into details, Om Next is a next generation UI building approach written in ClojureScript and based on React. Its architecture allows to really easily use Property based testing to ensure confidence in the various interactions that are possible in your interface.

If you want to read more, head to the official documentation : [Applying Property Based Testing to User Interfaces][quickcheck-ui].

I won't probably be able to use this in one of my next project, but I am really excited to see what comes of it and I hope developers from other frameworks will start to implement an architecture similar enough so we can all benefit from easier UI interaction testing.


[omnext]: https://github.com/omcljs/om/wiki/Quick-Start-(om.next)
[quickcheck-ui]: https://github.com/omcljs/om/wiki/Applying-Property-Based-Testing-to-User-Interfaces

Conclusion
----------

QuickCheck is probably easier to use in strictly typed functional programming language. It suffices to declare generators for certain types, some properties for your functions and nearly everything else can be done automatically.

It's a bit more difficult in dynamically typed languages, because you have to manually define which generator to use for which function and your confidence level is a bit lower since you are not sure side-effects won't arise and nullify some of your testing result.

However, this don't mean we can't benefit from some of the goodness ! I am personally always struggling to “invent” good test values and fore sure I always miss some edge cases. Those two issues are gone if you use Property Based Testing with a high enough number of iterations. Let the tool generate your data and just fix the bugs when the pop up.

Writing properties is not easy in the beginning, you have to take some distance with your code, but more often than not it helps you reason about the feature you are implementing and will probably lead to a better code because you took the time to think about it from a different angle.
