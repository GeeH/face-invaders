# Face Invaders — Game Design Document (v1)

Sep 27, 2026 · @Gary

## Overview

Face Invaders is a stationary auto-shooter that a stream's chat can play without the streamer touching it. A turret sits in the centre of the screen and automatically fires at the nearest enemy. Enemies fly in from the screen edges, each carrying the avatar of one of the streamer's Twitch followers.

**The hook:** between waves the game offers a choice of upgrades, and chat picks one by voting with a command like `!vote 1`. The streamer can play along or leave it running while they queue for a match or wait to load into a raid. For viewers, it beats staring at a loading screen.

**How a streamer uses it:** register on the web app with Twitch, copy a personal game URL from the dashboard, paste it into OBS as a browser source, and press a button to send the Face Invaders bot into their chat.

**How it's built:** live on stream with Claude Code, in an open-source repo. Later, viewers will contribute their own upgrades as pull requests.

## Core gameplay loop

A wave is a set number of enemies; clearing it pauses the game for a chat vote, and losing all health sends the run back to wave 1.

&#91;embedded content: core loop · one wave, one vote, one death branch\]

- **The base** sits fixed in the centre of the screen. It never moves.
- **Targeting** is automatic: the turret always fires at the nearest enemy.
- **Enemies** spawn at the screen edges and fly straight at the base. Each shows a follower's face.
- **Combat is one-hit for v1:** every enemy has 1 health and every bullet does 1 damage.
- **Taking damage:** an enemy that reaches the base is destroyed and the base loses 1 health. The player starts with 5 health.
- **Wave end:** the number of enemies per wave is configurable. The wave ends when the last enemy is gone, and no new enemies spawn during the vote.
- **Death:** at 0 health the run ends and restarts at wave 1 with upgrades reset. Only score and stats are kept.
- **Difficulty ramp:** each wave adds a set number of enemies and a set amount of enemy speed on top of the previous wave. Setting both increments to 0 gives flat waves for balance testing.

Starting health, enemies per wave, enemy speed, fire rate and the ramp increments all come from the balance panel (see Configuration).

## Upgrades and chat voting

After each cleared wave, chat has a fixed voting window to pick one upgrade, and the streamer can end the vote early by picking one themselves.

### v1 upgrades

| # | Upgrade | Effect |
| --- | --- | --- |
| 1 | Attack speed | Turret fires faster (amount set in the balance panel) |
| 2 | Heal | Restore 3 health, up to the current max health |
| 3 | Max Health | Adds one max health |

An attack damage upgrade is left out of v1 because every enemy has 1 health, so it would have no effect. It comes back with enemy variety.

The design should allow 3 to 5 options per vote later, drawn from a larger pool.

### Voting flow

1. The wave clears and the game pauses. The options appear on screen, numbered.
2. "Vote now" appears on screen. The Face Invaders bot only listens and never posts in chat.
3. Viewers vote with `!vote 1`, `!vote 2` and so on. Each viewer gets one vote.
4. The vote closes when the streamer's chosen window runs out, or immediately if the streamer picks an option from their dashboard.
5. The winning upgrade is shown on screen and applied, and the next wave starts.

**Ties and no votes:** a tie is settled by a random pick among the tied options. If nobody votes before the window ends, a random option is picked from all of them.

The voting window is a per-streamer setting, for example 30 seconds, 1 minute or 10 minutes.

Because the streamer can't easily click inside an OBS browser source, their override buttons live on the dashboard page and are pushed to the game in real time.

## Faces

Proving we can pull follower avatars from Twitch and put them on enemies is the first milestone of v1.

- **Source:** the streamer's Twitch followers, read with the permission granted when they log in. The follower list includes the date each person followed, which we keep for the future boss feature.
- **Enemy sprite:** each enemy is a generic sprite with a circle or square window in the middle where the avatar sits. The sprite still reads as an enemy if the window is empty.
- **Fallback:** small channels won't have enough followers for every enemy. We bundle a set of stock generic avatars and fill the gaps with those.
- **Opt-out:** not in v1. Avatars are public, but a way for viewers to remove themselves is logged as a future issue.
- **YouTube:** not in v1, but the face source should sit behind an interface so a YouTube provider can be added later.

### Names and priority

- **Names:** every enemy shows the viewer's username under their face.
- **Active viewers first (first thing after v1):** faces are drawn from a queue that favours recent activity, such as chatting, resubscribing or cheering bits. People watching right now see themselves far more often than followers who aren't there. The weighting gets tuned later.
- **Everyone else** fills the remaining slots, then stock avatars.

In v1, faces come from followers and then stock avatars. The bot already records when each viewer last chatted, so the priority queue can be added without a data migration.

## Tech architecture

A Laravel web app with a Phaser.js game, joined by websockets, with one shared chat bot serving every streamer.

&#91;embedded content: system architecture · 6 parts\]

- **Laravel app:** registration and login with Twitch, the streamer dashboard, the global balance panel, and the database for settings and stats.
- **Phaser game:** served at a unique URL per streamer, which they paste into OBS as a browser source. The game area is a fixed 1920×1080 that scales to fit the source, so balance behaves the same at any size, and the background is transparent. It loads its config and faces from Laravel and sends back stats at the end of a run.
- **Laravel Reverb:** pushes live events to the game, such as vote counts, the result and dashboard overrides.
- **Face Invaders bot:** one shared Twitch account, **FaceInvadersBot**, that joins a channel when the streamer presses the button. It reads `!vote` commands, tallies them, and never posts in chat; all prompts and results appear in the game. It never uses the streamer's own login.
- **Repo:** open source under the MIT licence from day one, built on stream with Claude Code.

Everything is hosted on **Laravel Cloud**: the app, Reverb, and the bot. The bot is a long-running process rather than a web request, so it runs as its own background process with restart handling. Exactly what the bot needs from Twitch to read a channel's chat is researched during the build.

## Configuration

Game balance lives in a global admin panel and is global only in v1; streamers only control their own stream settings. Per-channel balance overrides are a future idea.

| Setting | Who sets it | v1 default |
| --- | --- | --- |
| Starting health | Admin (global) | 5 |
| Enemies per wave | Admin (global) | TBD |
| Enemy health | Admin (global) | 1 |
| Bullet damage | Admin (global) | 1 |
| Enemy speed | Admin (global) | TBD |
| Extra enemies per wave (ramp) | Admin (global) | TBD |
| Extra enemy speed per wave (ramp) | Admin (global) | TBD |
| Turret fire rate | Admin (global) | TBD |
| Attack speed upgrade amount | Admin (global) | TBD |
| Heal upgrade amount | Admin (global) | 3 |
| Number of upgrade options per vote | Admin (global) | 3 |
| Voting window length | Streamer | TBD, e.g. 30 s to 10 min |

The game reads these at the start of each run, so balance changes apply without a redeploy.

## v1 scope

v1 is done when a streamer can log in with Twitch, drop the game into OBS, and have chat vote on upgrades between waves of follower-faced enemies.

**In v1**

- [ ] Register and log in with Twitch
- [ ] Pull the streamer's followers and their avatars
- [ ] Enemy sprite with an avatar window and username, plus stock fallback avatars
- [ ] Enemies fly from the screen edges to a central base; the turret auto-targets the nearest
- [ ] Waves of a configurable size that ramp up each wave, ending when the last enemy is gone
- [ ] Five health; restart at wave 1 on death
- [ ] Three upgrades: attack speed, heal, max health
- [ ] Shared bot joins chat from a dashboard button; `!vote N`, one vote per viewer
- [ ] Streamer-set voting window and dashboard override; ties and empty votes resolved by random pick
- [ ] Browser source URL on the dashboard
- [ ] Global balance panel
- [ ] Stats saved per run: score, wave reached, kills, duration and the upgrades picked

**Not in v1:** the active-viewer priority queue, YouTube, bosses, meta-progression between runs, the community upgrade hook system, viewer opt-out, per-channel balance overrides, and anything beyond one-hit enemies.

## Future ideas and logged issues

These are deliberately out of v1 but should shape how v1 is structured.

- **Active-viewer priority queue:** the first thing after v1 (see Names and priority).

- **Community upgrades:** a hook system so viewers can add upgrades by pull request, such as bullets that grow and shrink or a spinning force field. v1's upgrade code should be written so new upgrades plug in without touching the core loop. Every community pull request is reviewed and tested before merging.
- **Bosses:** the longest-standing followers or subscribers appear as bosses at set waves (for example 10, 20, 30), tuned from the balance panel.
- **YouTube support:** a second face and chat provider. The API limits need researching first.
- **Viewer opt-out (issue):** a way for viewers to keep their avatar out of the game. Deferred until it's needed.
- **Meta-progression:** permanent upgrades that carry between runs.
- **Enemy variety:** enemies with more health, different speeds or special behaviour.
- **Deeper voting rules:** changing votes, on-screen live counts, other tie-breaking rules.
- **Per-channel balance:** let streamers override selected global balance settings for their own channel.

## Open questions

None right now. The first round was answered on Sep 27, 2026 and the answers are folded into the sections above.
