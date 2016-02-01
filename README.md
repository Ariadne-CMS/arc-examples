ARC: Ariadne Component Library 
========================= 

[![Latest Stable Version](https://poser.pugx.org/arc/arc/v/stable.svg)](https://packagist.org/packages/arc/arc)
[![Total Downloads](https://poser.pugx.org/arc/arc/downloads.svg)](https://packagist.org/packages/arc/arc)
[![Latest Unstable Version](https://poser.pugx.org/arc/arc/v/unstable.svg)](https://packagist.org/packages/arc/arc)
[![License](https://poser.pugx.org/arc/arc/license.svg)](https://packagist.org/packages/arc/arc)

A flexible component library for PHP 5.4+ 
----------------------------------------- 

ARC is a set of components, build to be as simple as possible. Each component does just one thing and has a small and 
simple API to learn. ARC uses static factory methods to simplify the API while using Dependency Injection. ARC is not a
framework. It can be used in combination with any framework or indeed without.

The Ariadne Component Library is a spinoff from the Ariadne Web Application Platform and Content Management System 
[http://www.ariadne-cms.org/](http://www.ariadne-cms.org/). Many of the concepts used in ARC have their origin in Ariadne
and have been in use since 2000. 

A unique feature in most components is that they are designed to work in and with a tree structure. URL's
are based on the concept of paths in a filesystem. This same path concept and the underlying filesystem-like tree is
used in most ARC components. 

Examples
--------

This repository contains example programs using differen ARC components. They aren't meant for production use.
The focus is on how to use ARC components, not on building complete and robust applications.

Installation
------------

Via [Composer](https://getcomposer.org/doc/00-intro.md):

    $ composer create-project arc/examples {$path}
    
