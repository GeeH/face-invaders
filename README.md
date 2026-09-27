# Face Invaders

A stationary auto-shooter that a Twitch stream's chat can play without the streamer touching it.

A turret sits in the centre of the screen and automatically fires at the nearest enemy. Enemies fly in from the screen edges, each carrying the avatar and username of one of the streamer's followers. Between waves the game offers a choice of upgrades, and chat picks one by voting with `!vote 1`, `!vote 2` and so on.

It's built for the dead time on stream: queueing for a match, waiting to load into a raid. For viewers, it beats staring at a loading screen.

> **Status:** early development, built live on stream with Claude Code. Nothing is playable yet. Progress is tracked in the [milestones](https://github.com/GeeH/face-invaders/milestones).

## How a streamer uses it

1. Register on the web app with Twitch.
2. Copy your personal game URL from the dashboard.
3. Paste it into OBS as a browser source (1920×1080).
4. Press the button on the dashboard to send **FaceInvadersBot** into your chat.

The bot only listens for `!vote` commands. It never posts in chat, and it never uses your login. All prompts and results appear in the game.

## How it's built

- **Laravel** web app for Twitch login, the streamer dashboard, the global balance panel, and settings and stats.
- **Phaser** game served at a unique URL per streamer, used as an OBS browser source.
- **Laravel Reverb** pushes live vote events and dashboard overrides to the game.
- **FaceInvadersBot**, one shared Twitch account that reads `!vote` commands in every enabled channel.

The full design, including gameplay, upgrades, voting, configuration and v1 scope, is in the [Game Design Document](docs/Face%20Invaders%20%E2%80%94%20Game%20Design%20Document%20(v1).md).

## Contributing

Contributions are welcome, and later on viewers will be able to add their own upgrades by pull request. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Licence

[MIT](LICENSE)
