Functional programming techniques in PHP
========================================

Functional programming has gained a lot of tractions those 3 to 5 last years.
First, there are some success story around it : Twitter move to Scala, Whatsapp
being written in Erlang. Then we have some new kick-ass languages like Rust.
Finally, it seems the new hype is to create a functional language compiling
to javascript : Elm, Purescript. On a more academic front, there is also
research on new concepts like dependant typing, see Idris for example.

Those are all cool and shiny new toys, but we can benefit from some techniques
without having to learn a new tool, just by applying some principles to our
everyday PHP ! But first of all, what exactly is functional programing ?


Functional programming
----------------------

Functional programming is a XXX as are imperative, logical and OO programming.
The main point is that everything is a function, in the mathematical sense. The
corollary of that is that you cannot have side effects since all outputs depends
solely on the input of the function. It also means you cannot have any mutable state,
once something was computed, it cannot be changed to something else.

This can be cumbersome a time, so a distinction was made between pure functional
languages (like Haskel or Ocaml) and other function languages, Scala for example,
where you can introduce mutable variables or even do plain old Java.

Another issue are input and outputs which are by definition mutable, the "problem"
has been resolved in most functional languages by wrapping them into a Monad, the rest
of this blog post will not talk about that, so I won't explain this barbaric word,
but feel free to do some research on the internet, it is really interesting.

**Despite all those lenthy explanation, functional programming is first and for all
a way of thinking and solving issues than can be applied in any languages**. It is
just easier to do in some that are designed for it.


The advantages
--------------

Since the output depends solely on the input, reasoning about a function is made a whole lot
easier, you have everything under the eyes, there is no need to keep in mind any kind of
global or shared state, thus reducing complexity a lot.

Testing is made easier, you don't have to mock a lot of dependencies or create big ass
objects, you just need to pass the arguments you function need.

A functional style also often result in writing small and self-contained functions used
later as building blocks for bigger functionality, resulting in clearly decoupled code
and extended reusability.

A nice little bonus of a pure function is that you can cache its result, this is called
memoization but more about that later.

A perhaps less appealing argument for us, is that calls to pure function can easily be
distributed across threads or even computers because you have the guarantee that the
inputs are enough to perform the computation and that there will be no side effects.
It is simply a matter of combining the results again at the end.


The basics
----------

The first important thing is that functions are, more or less, first class citizen in
the PHP world :

    // Function as a variable
    $func = function() {
        return 42;
    };

    // Function as a return type
    function createFunction() {
        return function() { return "Thanks for all the fish"; };
    };
    $func2 = createFunction();

    // Function as a parameter
    function display($func) {
        echo $func()."\n\n";
    }

    // Call a function by name
    call_user_func_array('strtoupper', $someString);

    // objects as functions
    class SomeClass {
	    public function __invoke($param1, $param2) {
	        [...]
	    }
	}
	$instance = new SomeClass();
	$instance('First', 'Second'); // call the __invoke() method

Knowing that, we can start playing around with basic functional concepts. For each example,
the "common" way of doing things will be presented left and the functional way on the right.
As it is often the case in functional programming, we will most of the time manipulate an
array.


Applying a function to all elements :

	foreach($k => $v in $stringArray) {
		$stringArray[$k] = strtoupper($v);
	}
	-------------------------------------
	$result = array_map($stringArray, 'strtoupper')


"Reduce" an array :

	$result = 0;
	foreach($v in $intArray) {
		$result += $v;
	}
	-------------------------------------
	$result = array_reduce($intArray, function($a, $b) { return $a + $b; }, 0)

This particular example might not be a good one, but keep in mind that the applied function
can be of any particular complexity, and that you can declare and use it in other places.

It is also important to note that PHP offers an `array_sum` function for this particular case :
$result = array_sum($intArray);


Filter an array :

	$result = array();
	foreach($v in $intArray) {
		if($v % 2 === 0) {
			$result[] = $v;
		}
	}
	-----------------------------
	$result = array_filter($intArray, function($a) { return $a % 2 === 0); })


Some utility functions
----------------------

In most functional languages and library, you will also found functions that operates on
predicate (a test that return a boolean) and arrays. The most common examples are :

* `first` / `last` which returns the first, respectively last, element of an array matching the predicate

* `any` which returns true as soon as one element matches the predicate

* `all` which returns true if all elements matches the predicate

Those don't exists in PHP but you can find various implementations on the github repository I
created for the occasion : <some github repo URL>

This kind of function can be useful for example in right management when you have to see of the
connected user has at least one role with a particular set of permission or to verify that you
can apply a particular transformation on all elements in an array :

	// check for an administrative right
	if(any($roles, function($r) { return $r->isAllowedToTranslate(); }) {
		[do something]
	}

	// check if you can apply an operation to a set of elements
	if(all($elements, function($e) { return $e->canBeDeleted(); }) {
		[do something]
	}


Once you have create all those little building blocks to manipulate your data, you can
then compose them to perform more complex operations. Sadly PHP don't propose a `compose`
function, but you can again find an implementation in the repository linked above.

	Find some kickass composition example


There would be a lot of others thing to say or show, but I don't want to overwhelm you too much
and there is already a lot of really interesting resources available on the web :

* find
* some
* fricking
* links


Memoization
-----------

The last thing I want to talk about is memoization or, put in other words, function results
caching.

Remember when we said that the result is solely dependent on the input, now say we have a
computational heavy function that is called multiple times with the same parameters. Knowing
that this is a pure function means you can just cache the result the first time and then simply
return hit the cache. This is exactly the same thing that using caching for web URLs.

You can do that really easily using static local variables in PHP :

	function factorial($n) {
	    static $cache = array();

	    if($n == 1) return 1;

	    if(! array_key_exists($n, $cache)) {
	        $cache[$n] = $n * factorial($n - 1);
	    }

	    return $cache[$n];
	}

You can also generalize this mechanism using a nifty function that will return a memoized version
of any pure function :

	function memoize($func) {
	    return function() use($func) {
	        static $cache = array();

	        $args = func_get_args();
	        $key = serialize($args);
	        if(! array_key_exists($key, $cache)) {
	            $cache[$key] = call_user_func_array($func, $args);;
	        }
	        return $cache[$key];
	    }
	}

You can then simply do the following :

	$factorial = function($n) use(&$factorial) {
	    if($n == 1) return 1;
	    return $n * $factorial($n -1);
	}
	$mem_factorial = memoize($factorial);


Et voilà ! You just built a cached version of a pure function.


Conclusion
----------

Functional programing is not necessarily this difficult thing we are something lead to believe it is.
You don't need to learn a brand new language to start applying the principle in your daily work,
you can even start with tiny little steps and take your time to build up your expertise !

In most of the case, having a "functional mindset" makes your code easier to reason with and automatically
promotes common best practices like "Single Responsability Principle" and "Reusability". You even have a
total absence of side effect for free if you write pure functions !

Someone once said *Functional languages enforce what is simply "good code" in other languages.*

Also, learning different ways to do things can only open your mind and make you a better developer.

This is why I encourage you to try techniques discussed in this post and why not take the more radical
decision to try one of those obscure pure functional language that some bearded guy are talking about
in conferences. I can't promise you that you will fall in love and don't come back to PHP, but I am sure
that you will learn a lot in the process !

If I could only say one last thing, it would be : "Always strive to learn new things and don't stay in your comfort zone".

