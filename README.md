# cisco-port-status

PHP Script originally to log into IOS-XR and show a `show int status` style output, also supports IOS normal.

Routers the script knows how to log into are defined in `routers.ini` (see `routers.ini.example`)

You can add TX/RX Power columns with `--power`, and only check specific interfaces with `--int`.

Using `--int` will auto-expand any port-channels to show the member ports also.

Output can also be given as JSON using `--json` because why not!
