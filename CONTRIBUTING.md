# Contributing

Thank you for looking. This library is the shared part of Pack-Out Spot, and a change here reaches both plugins.

## Before you start

- For a bug, open an issue with what you did, what you expected, and what happened.
- For a new act or a change to what a person can do, open an issue first. The capability list is the agreement between the two plugins, and a row is easier to discuss than a pull request.
- For a security problem, do not open an issue. See [SECURITY.md](SECURITY.md).

## Making a change

1. Fork the repository and make a branch.
2. `composer install`
3. Make the change. Add or change a test in `tests/` that fails without it.
4. `composer check` must pass: the versions agree, `docs/capabilities.md` is current, the unit tests pass, and the code meets the WordPress coding standards.
5. Open a pull request that says what changes for a person using Pack-Out Spot, and how you checked it.

## What a change must keep

- **No WordPress hooks at load.** The library registers nothing until a plugin builds a kernel and asks.
- **No Vectra, no theme.** Anything the library needs from outside WordPress core goes through an adapter in `src/contracts/`.
- **The capability list comes first.** An act is added to `capabilities.php`, with a reason when only one plugin has it, before a handler names it. Run `php bin/capabilities-doc.php` afterward.
- **Text is translatable**, with the text domain `packout-core`.
- **The contract only grows.** Sites of earlier versions go on talking to packout.spot. A schema in `contract/v1/` never loses a field or narrows one. A change that adds a schema or a field runs `php bin/freeze-contract.php` and declares the addition in `tests/compat-test.php`, which fails until it does. `contract/v1/README.md` has the rule.
- **Tests run with PHP alone.** `tests/bootstrap.php` stands in for the few WordPress functions the library calls.

## Style

WordPress coding standards, checked by `composer lint`. Tabs for indentation. Every class, method, and file has a doc comment that says what it is for.

## Versions

The maintainers set the version and tag releases. Do not change `PACKOUT_CORE_VERSION` in a pull request.

## License

By contributing you agree that your contribution is licensed under GPL-2.0-or-later, the license of this repository.
