QuickCheck is coming to PHP
===========================

TLDR; Just try [php-quickcheck][1], it is a great library in the making to ease testing pain !

Tired of trying to find meaningful values to feed your methods while unit testing ?

What if I told you it suffice you just define some generators and then hundreds or
even thousands of randomly selected values could be fed as parameters to test some of
the edge cases you didn't even think about ?

[1]: https://github.com/steos/php-quickcheck

In a few words
--------------

Nearly 15 years ago, Jon Hugues and Koen Claessen, from Haskell fame, described and formalized
a novel way to do testing. Instead of classical unit testing where the developer has to write
assertion for various possible parameters to a function, they decided to write a tool allowing
to describe *properties* of the tested function. The tool then try to feed various random
values as parameter to the function and test if the properties hold.

The [original paper][2] probably describes everything better than I could ever do, but in short,
the idea is to define two things :

* A way to define properties for the tested function
* A way to define generators for random values

A property is basically a predicate that must hold for every possible values, usually testing
that the function return something meaningful is sufficient.

A generator is a function creating random values for a given parameters. Generators already
exists for basic types (integer, strings, boolean), but to give maximum flexibility to the
tester, he can define its own.

Then, you simply let QuickCheck do the grunt work, it will call the function multiple times,
be it a hundred or a thousand, with random values and report any time the property failed
to verify.

One other great feature of QuickCheck is that, once he finds a value that failed to validate
the property, it tries to reduce the input to the simplest failing case. For example, if you
test a sorting function, instead of providing you with some bug array it will try to find the
smallest array that produce a wrong result.

This approach allows to more clearly thing about what the system is supposed to do instead of
focusing on finding test values. It can also fairly easily discover issues with edge cases that
are really difficult to think about.

[2]: http://www.eecs.northwestern.edu/~robby/courses/395-495-2009-fall/quick.pdf

Fuzzing
-------

In a way, you could compare QuickCheck to fuzzing individual function.

For those not familiar with the term, [fuzzing][3] is the idea of feeding random data as input
to a program to see how it reacts. It is often used on native application to discover memory leaks
or possible security attack vectors.

You can also use fuzzing on web apps, for example to detect if user inputs are correctly sanitized.
OWASP has a page about using fuzzing this way : [https://www.owasp.org/index.php/Fuzzing][4]

There are however two main differences between the two techniques :

* Fuzzing considers the program as a black box whereas QuickCheck try to validate a list of known
properties
* Fuzzing performs on the program level whereas QuickCheck is used on individual functions.

In other words, Fuzzing is to functional testing what QuickCheck is to unit testing.

[3]: http://en.wikipedia.org/wiki/Fuzz_testing
[4]: https://www.owasp.org/index.php/Fuzzing

What about PHP ?
----------------

You can guess that this idea interested a lot of people. Implementation of QuickCheck exists in more than
two dozen languages. PHP is no exception, over the time there were multiple tries to develop the concept
in PHP. Recently, one of the attempt caught my eye : [php-quickcheck][1].

The core of the library may be a bit hard to grasp because it uses lot a functional techniques to be as
close as possible to the original. However it is the first PHP version that includes the automagical
input shrinking I discussed earlier.

Before going further into the explanation, let's start with a real quick example taken straight from the
readme of the project :

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

The interesting part is the last one, where we use QuickCheck to test our previously defined
sorting function. Our property states that for all (`Gen::forAll`) integer arrays (`Gen::ints()->intoArrays()`)
the result of `myBrokenSort` should be ascending. Seeing the implementation of the function,
it should be fairly clear that it won't be the case, but let's see what the output is :

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

This tells us that QuickCheck found an array falsifying the predicate after 7 tests, the original
test value is `[-3,6,5,-5,1]`. Then, it successfully tried to shrunk it, the minimal case that
invalidate the property is : `[1,0]`, which seems about right.

The library currently ship with generators for most of the scalar types. You also have a lot of
methods to combine multiple generators and create new values from them. I'll let you have a look
at the [README][5] for more details.

[5]: http://github.com/steos/php-quickcheck/README

### Not everything is great, at least for now

Sadly, the php-quickcheck library is still in its infancy and besides the generators and the check
mechanism, nothing is really available. If you want to test multiple properties on a function, you
will have to write a lot of boiler plate code.

A way to interact nicely with PHPUnit is currently in discussion, but sadly it is not that easy as
PHPUnit evaluates each data set before running the tests suites, thus generating a lot of in memory
data and considerably slowing the bootstrap process.

Any contribution, or simply ideas and suggestions are more than welcome on the project [bug tracker][6] !

[6]: http://github.com/steos/php-quickcheck/issues


Practical example : Splitting strings
--------------------------------------

If you're still not convinced by this approach, let's dirty our hands a bit with a practical
example : we will implement a function to split strings based on a separator.

As a disclaimer, this is not an exercise in clean code or performance, the idea is simply to
demonstrate QuickCheck power. Let's also assume that we don't know about the `explode` function
already.

All the boiler plate code is left out from this post for clarity, you can find it on the
[dedicated gist][7], along the definition of all properties definition and the various
implementation tries.

[7]: some.gist.url

Conclusion
----------


