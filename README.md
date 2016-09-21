# SimpleMVC framework for TYPO3 CMS

**WARNING** This extension is obsolete and will not be updated anymore. You are encouranged to use Extbase and TYPO3 >= 6.2.

---

This is a TYPO3 "simplemvc" extension. It adds a simple high performance MVC framework to TYPO3.

The extension allows you to have a full MVC stack without using any heavy overheads or obsolete methods. The goals of this project are:
* High performance solution. Performance comes first here.
* Clean code. Clean code is next to performance.
* Developers must be free from annoying and time consuming operations (such as defining properties for database fields, loading language labels manually, etc).
* AJAX applications must be easy with this framework. AJAX classes must have nearly the same possibilities as their normal Frontend classes.
* Support for features often needed by programmers: CSRF, ReCaptcha, etc.
* Compatibility in interfaces as much as possible across versions.

## How it works

There are three main components:
* Controllers
* Models
* Views

### Controllers

Controllers handle _actions_. Actions are methods inside the controller that look like <code>indexAction</code> (the default action if no parameters are given) or <code>formAction</code>. Actions are passed as parameters to the controller.

Controllers contain many helper methods to get data from the configuration, use language labels, handle ReCaptcha or CSRF protection.

### Models

Models contain various predefined methods such as <code>getId</code> or <code>save</code> to manipulate models. There is no separate _database handler_ or _model repository_ because those components do not fit into the MVC model. So the model is responsible for loading and saving data. It knows when to insert or update records.

Models do not require to define properties or methods to fetch data, though the programmer can define some methods. Normally <code>$model-&gt;getMyField()</code> or <code>$model-&gt;setMyField()</code> will magically get and set the <code>my_field</code> for you.

Models can handle certain relations and return instances of other models. This requires minimal configuration in the model class.

### Views

Views render the content. It is up to the view how to render the content.

## Versions

The extension exists in two main branches. Branch _TYPO3_4x_ is mature and used in production on some high load TYPO3 web sites. Branch _master_ is currently in development and it is compatible with TYPO3 version 6.x. Most of the code in the _master_ branch is a simple backport from the TYPO3_4x branch.

## Plans

There are plans to extend the framework with certain features:
* Make a managing extension for the SimpleMVC framework. The managing extension should make the development easier by providing a kickstarter and a helper module to add controllers, models, views, actions, etc.
* Make (or backport from a provate repo) sample extensions (such as news) that show how to use the framework
* Write a better documentation

## Relation to Extbase, Fluid, tslib_pibase

No relation whatsoever.

## Contacts

The extension is created and developed by [Dmitry Dulepov](https://github.com/dmitryd) with the help of contributors. Current contributor list includes:
* [Elmar Hinz](https://github.com/t3elmar)

Contributions are welcome as pull requests. However, please, keep separate commits for each of your changes and make separate pull requests for each of them.

Thank you!
