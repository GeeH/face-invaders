# Live events (Reverb)

Laravel pushes live events to each streamer's game over [Reverb](https://reverb.laravel.com).

## Channel

Each streamer has one **public** channel, `play.{play_token}` (`User::gameChannel()`). The game is an OBS browser source that can't log in, so the channel can't be private. The 40-character play token is the secret instead, the same as for the game URL. Regenerating the token moves the channel, so a leaked URL stops getting events too.

## Events

Events extend `App\Events\Game\GameEvent`. They're broadcast straight away rather than queued. Each one sets its name with `broadcastAs()` and its exact payload with `broadcastWith()`, so nothing else about the streamer leaks to the browser. In the game, `resources/js/game/live.js` subscribes to the channel, and `main.js` passes each event on as a Phaser game event.

| Event | Name | Payload | Status |
|---|---|---|---|
| `Ping` | `ping` | `{ message }` | Built. Shows `message` as a banner. |
| `VoteOpened` | `vote.opened` | `{ id, options: [{ number, id, name, description }], closes_at }` | Built (#23) |
| `VoteTallyUpdated` | `vote.tally` | `{ id, tally: { [number]: votes } }` | Built (#23) |
| `VoteClosed` | `vote.closed` | `{ id, winner, tally }` | Built (#23). `winner` is the upgrade's `id`. |

## Votes

When a wave is cleared, the game `POST`s the drawn upgrades to `/play/{token}/votes`. That opens a `VoteSession` for the streamer's voting window and replaces any vote still open, e.g. after a restart. A queued `CloseVoteSession` job closes it when time runs out. You need the queue worker running for this, and `just up` starts it.

- **One vote per viewer:** voting again changes your vote. Numbers that aren't on offer are ignored.
- **Winner:** the most votes wins. On a tie, one of the leaders is picked at random. With no votes, any option is picked at random.
- **Override:** `close(VoteClosedBy::Override, $upgradeId)` picks the winner outright (dashboard, #25). Whichever close happens first wins, so the timer does nothing afterwards.

## Trying it locally

`just up` starts Reverb on `ws://localhost:8080` next to the app. Open your game URL, then:

```sh
just artisan game:ping 1 "Hello chat"
```

The message appears as a banner in the game. `1` is the streamer's user ID.

## Configuration

- `REVERB_HOST` is how Laravel reaches Reverb. Inside Docker, that's the `reverb` service.
- `VITE_REVERB_HOST` is where the browser connects. Locally that's `localhost`.
- Laravel Cloud setup is part of #5.
