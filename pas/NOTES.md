# bot.lpr — port notes

A single-file FreePascal/Lazarus port of `bot.php`. No third-party units —
everything used ships with a stock FreePascal 3.2.2 install (RTL, FCL, and
the `openssl` binding FPC bundles for TLS). Build with:

    fpc -Mobjfpc -Sh -O2 bot.lpr

Run with a config file (key=value, `#` comments):

    ./bot bot.conf

## What's implemented and tested

Verified end-to-end against a local fake IRC server (connect, register,
respond to numeric 376, PING/PONG, dispatch, spawn, reply):

- Plain-TCP connect/register (NICK/USER), PING/PONG keepalive
- Exec-file DSL: pipe-delimited alias lines, `include:`/`init:`/`startup:`/
  `help:` directives, the `~alias-macro` live-editing command
- Alias matching → process spawn via `/bin/sh -c`, with `%%placeholder%%`
  template substitution (each value shell-quoted, same as the PHP
  original's `escapeshellarg()`-based templating)
- Non-blocking stdout/stderr pumping, control lines from spawned processes
  (`/IRC`, `/PRIVMSG`, `/EXEC-ADD`, `/EXEC-DEL`, `/BUCKET_GET|SET|UNSET|APPEND`,
  pause/unpause)
- Bucket get/set/unset/append, round-tripped through a real spawn
- Timeout-based process killing, reap-on-exit, bucket-lock release

## What's implemented but NOT independently tested here

I have no network egress to a real IRC server in this environment, so the
following compiles cleanly and is built from FPC's own documented/source
APIs, but hasn't seen a live handshake:

- **TLS** (port 6697/6698/9999 auto-selects it) via FPC's bundled
  `opensslsockets`/`sslsockets` units, which wrap the system's `libssl` at
  runtime. Verify this against your actual server before relying on it in
  production — in particular check `SSL_CA_FILE`/certificate verification
  behavior on your platform.
- WHOIS-based admin authentication (numeric `330` flow) — the numeric
  parsing is textbook IRC, but I couldn't fire a real WHOIS round-trip.
- NickServ IDENTIFY-prompt handling.
- Process-tree kill (`~kill`/`~killall`) — shells out to `ps -eo pid,ppid`
  and walks descendants; the parsing logic is straightforward but untested
  against a real process tree with grandchildren.

## Intentional differences from the PHP original

1. **Buckets file format.** Not PHP-`serialize()`-compatible (per your
   call, since there's no legacy data to migrate) — uses a small
   custom structured-value encoder (`PhpSerialize`/`PhpUnserialize` in the
   source; the names are legacy from an earlier draft, the format itself is
   our own).
2. **Connection liveness.** Rather than relying on `read() == 0` meaning
   "connection closed" (ambiguous once TLS is involved — a timed-out
   non-blocking SSL read can also surface as 0), liveness is judged by a
   PING/PONG watchdog: no data for 300s forces a reconnect; no data for
   120s triggers a PING. This is more robust than the original's
   `feof()`-based check but is a deliberate behavioral change worth knowing
   about.
3. **`startup:` directive firing.** In the PHP original, `startup()` only
   fires as a side effect of receiving a *specific* NickServ NOTICE prompt
   string — if your network never sends that exact notice (no NickServ, or
   you're already identified), startup execs never run. This port instead
   fires them once, right after numeric `376` (end of MOTD), which is far
   more reliable. Functionally a superset of the original behavior.
4. **Restart (`~restart`).** Uses a real `execve()` re-exec of the running
   binary (`BaseUnix.FpExecve`) on Unix, which mirrors PHP's `pcntl_exec`
   intent closely. No equivalent is implemented for Windows (logs and exits
   instead — restart there would need a supervisor/wrapper).
5. **IFACE (named-pipe output).** Implemented with `fpMkFifo`/`fpOpen` on
   Unix only; disabled with a log line if `IFACE_ENABLE=1` on Windows.

## Known gaps / simplified areas

These exist in the PHP original but are simplified or omitted here —
flagging them explicitly rather than silently dropping them:

- `~exec-conflicts` and detailed `~exec-timers` reporting are not
  implemented (the underlying data — `ExecList`, `RepeatLastFired` — is all
  there if you want to add them).
- The admin-auth "one in-flight WHOIS at a time" behavior is carried over
  verbatim from the PHP original (a second admin command arriving while one
  is still awaiting WHOIS verification is silently dropped) — this was a
  limitation in the original too, not introduced here.
- MySQL logging (`MYSQL_LOG`) depended on an external, not-included PHP
  file in the original and was already a dead code path there; not ported.
- `~alias-macro`'s reserved-key deletion path (deleting a non-reserved
  struct field) isn't meaningful here since aliases are a fixed Pascal
  record rather than PHP's free-form associative array — the "delete"
  action against a non-reserved key reports "not found", matching the only
  reachable outcome in the original for a fixed-schema alias.

## Testing recommendation before production use

1. Point `IRC_HOST`/`IRC_PORT` at your real server on a throwaway nick/
   channel first.
2. Test the TLS path specifically if you use it (port 6697 etc.) — watch
   for certificate verification behavior if you set `SSL_CA_FILE`.
3. Test the WHOIS-based admin flow with a real registered/identified nick
   before trusting `~kill`/`~restart`/etc. in production.
4. Load your real exec file with `~exec-errors` afterward to confirm every
   alias line parsed as expected — the pipe-delimited format is unforgiving
   about field count.
