# SimpleMVC framework for TYPO3 CMS

This is a TYPO3 "simplemvc" extension. It adds a simple high performance MVC framework to TYPO3.

The extension allows you to have a full MVC stack without using any heavy overheads or obsolete methods. The goals of this project are:
* High performance solution. Performance comes first here.
* Clean code. Clean code is next to performance.
* Developers must be free from annoying and time consuming operations (such as defining properties for database fields, loading language labels manually, etc).
* AJAX applications must be easy with this framework. AJAX classes must have nearly the same possibilities as their normal Frontend classes.
* Support for features often needed by programmers: CSRF, ReCaptcha, etc.
* Compatibility in interfaces as much as possible across versions.

## Versions

The extension exists in two main branches. Branch "TYPO3_4x" is mature and used in production on some high load TYPO3 web sites. Branch "master" is currently in development and it is compatible with TYPO3 version 6.x. Most of the code in the master branch is a simple backport from the TYPO3_4x branch.

## Plans

There are plans to extend the framework with certain features:
* Make a managing extension for the SimpleMV framework. The managing extension should make the development easier by providing a kickstarter and a helper module to add controllers, models, views, actions, etc.
* Make (or backport from a provate repo) sample extensions (such as news) that show how to use the framework
* Write a better documentation

## Relation to Extbase, Fluid, tslib_pibase

No relation whatsoever.

## Contacts

The extension is created and developed by [Dmitry Dulepov](https://github.com/dmitryd) with the help of contributors. Current contributor list includes:
* [Elmar Hinz](https://github.com/t3elmar)

Contributions are welcome as pull requests. However, please, keep separate commits for each of your changes and make separate pull requests for each of them.

Thank you!