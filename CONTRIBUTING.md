# Contributing to Face Invaders

Thanks for wanting to help! Face Invaders is built live on stream and is open source from day one.

## Before you start

- Read the [Game Design Document](docs/Face%20Invaders%20%E2%80%94%20Game%20Design%20Document%20(v1).md) so you know what v1 is and isn't.
- Work is tracked as [issues](https://github.com/GeeH/face-invaders/issues) grouped into [milestones](https://github.com/GeeH/face-invaders/milestones). Pick an existing issue, or open one to discuss your idea before writing code.
- Issues labelled `future` are deliberately out of v1 scope. Please check in on the issue before starting one.

## Local setup

_Coming soon._ This section will be filled in once the Laravel app is scaffolded (#3).

## Branches and pull requests

- Branch names follow `<milestone>/#<issue>-<short-description>`, for example `M0/#2-licence-readme-contributing`.
- Keep each pull request focused on one issue, and link it in the description with `Closes #<issue>`.
- Include tests for new behaviour, and make sure the test suite and linting pass. CI runs both on every pull request.
- Describe what changed and how you tested it. For anything visual in the game, a screenshot or short clip helps.

## Review

**Every pull request is reviewed and tested before it's merged**, and that includes community contributions. This matters most for upgrades: they run on other people's streams, so they need to be safe, perform well, and fit the game's balance.

## Community upgrades

The upgrade system is being built so that new upgrades plug in without touching the core game loop (#17). A documented way to add your own upgrade is planned (#30). Until then, please hold off on upgrade PRs.

## Licence

By contributing, you agree that your contributions are licensed under the [MIT licence](LICENSE).
