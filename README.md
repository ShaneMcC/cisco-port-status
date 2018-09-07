# cisco-port-status

PHP Script originally to log into IOS-XR and show a `show int status` style output, also supports IOS normal for consistency.

Routers the script knows how to log into are defined in `routers.ini` (see `routers.ini.example`), also make sure to check out the submodules: `git submodule update --init --recursive`

You can add TX/RX Power columns with `--power`, and only check specific interfaces with `--int`.

Using `--int` will auto-expand any port-channels to show the member ports also.

Output can also be given as JSON using `--json` because why not!

Example outputs:

```
$ ./portstatus.php --power --int BE10 asr1
Port                Name                          Status              Duplex         Speed     Type                Tx Power                      Rx Power
-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
BE10                Transit: Some transit supp... up                  --             --        --                  --                            --
Te0/0/0/1           Some transit supplier (Cir... up                  Full Duplex    10Gbps    SFP-10G-LR-C         0.65910 mW (-1.81049 dBm)     0.31550 mW (-5.01001 dBm)
Te0/1/0/1           Some transit supplier (Cir... up                  Full Duplex    10Gbps    SFP-10G-LR-C         0.68410 mW (-1.64880 dBm)     0.34690 mW (-4.59796 dBm)
...
Te0/2/0/1           Some transit supplier (Cir... up                  Full Duplex    10Gbps    SFP-10G-LR-C         0.52950 mW (-2.76134 dBm)     0.14330 mW (-8.43754 dBm)
$
```

```
$ ./portstatus.php --power --int po174 dc6core1
Port                Name                          Vlan                Status              Duplex         Speed     Type                Tx Power                      Rx Power
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Po174               Transit: Some transit supp... routed              up                  a-full         10G       --                  --                            --
Te1/1               Transit supplier              routed              up                  full           10G       10Gbase-LR          -3.1 dBm                      -2.5 dBm
Te2/2               Transit supplier              routed              up                  full           10G       10Gbase-LR          -3.1 dBm                      -3.2 dBm
$
```
