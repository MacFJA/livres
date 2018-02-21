# How to contribute

## Reporting and improving

### Did you find a bug?

* **Ensure the bug was not already reported** by searching on GitHub under [Issues](https://github.com/MacFJA/livres/issues).

* If you're unable to find an open issue addressing the problem, [open a new one](https://github.com/MacFJA/livres/issues/new). Be sure to include a **title and clear description**, as much relevant information as possible

### Did you write a patch that fixes a bug?

* Open a new GitHub pull request with the patch.

* Ensure the PR description clearly describes the problem and solution. Include the relevant issue number if applicable.

### Do you have an idea to improve the application?

* **Ensure the suggestion was not already ask** by searching on GitHub under [Issues](https://github.com/MacFJA/livres/issues).

* If you're unable to find an open issue about your feature, [open a new one](https://github.com/MacFJA/livres/issues/new). Be sure to include a **title and clear description**, as much relevant information as possible

### Do you want to contribute to the application documentation?

* **Ensure the documentation improvement was not already submitted** by searching on GitHub under [Issues](https://github.com/MacFJA/livres/issues).

* If you're unable to find an open issue addressing this, clone the wiki git on your computer

* [Open a new issue](https://github.com/MacFJA/livres/issues/new). Be sure to include a **title and clear description**, as much relevant information as possible and the patch for the documentation

## Coding conventions

The application use PSR-2 code conventions, strong typing.

The source code must be, at least, compatible with **PHP 7.0**.

Check your code by running **Edgedesign/phpqa** (dev dependency in the `composer.json`) with the command:
```sh
composer qa
```
The command will output a summary, and the detailed report can be found in the file `build/analysed/phpqa.html`

----

Thanks!