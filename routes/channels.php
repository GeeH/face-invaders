<?php

// The game listens on a public channel per streamer, `play.{play_token}`
// (User::gameChannel()). It's public because an OBS browser source can't
// log in; the unguessable token keeps it private, just as it does the
// game URL. Register private channels here if any are ever needed.
