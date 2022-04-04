# calc.pw Documents

This repository contains several documents that are related to the calc.pw password calculation.

## dieharder

To ensure the randomness distribution of the calc.pw key expansion, a full [dieharder](https://webhome.phy.duke.edu/~rgb/General/dieharder.php) run has been executed against [calcpw.php](https://github.com/yahesh/calcpw.php). The results can be found in the file [`dieharder/dist/calcpw.txt`](https://github.com/yahesh/calcpw.docs/blob/master/dieharder/dist/calcpw.txt). The dieharder run was executed with the following command and version **3.31.1** of dieharder, so the results should be reproducible:

```
$ ./calcpw.php --dieharder "password" "information" | dieharder -g 200 -a
```

The calc.pw key expansion has passed all dieharder tests. However the `sts_serial (ntuple = 6)`, `rgb_bitdist (ntuple = 11)` and `dab_filltree (ntuple = 32)` tests have only been passed as **weak** which may happen for PRNGs.

## drawio

The structure of the calc.pw key expansion has been visualized with the help of the open-source tool [draw.io](https://github.com/jgraph/drawio) which is also available at [app.diagrams.net](https://app.diagrams.net/). The source can be found in the file [`drawio/src/calcpw.drawio`](https://github.com/yahesh/calcpw.docs/blob/master/drawio/src/calcpw.drawio). The following renderings are available:

* PDF: [`drawio/dist/calcpw.pdf`](https://github.com/yahesh/calcpw.docs/blob/master/drawio/dist/calcpw.pdf)
* PNG: [`drawio/dist/calcpw.png`](https://github.com/yahesh/calcpw.docs/blob/master/drawio/dist/calcpw.png)
* SVG: [`drawio/dist/calcpw.svg`](https://github.com/yahesh/calcpw.docs/blob/master/drawio/dist/calcpw.svg)

## encryption

The ideas behind the calc.pw password calculation have been described in detail in [`encryption/ENCRYPTION.md`](https://github.com/yahesh/calcpw.docs/blob/master/encryption/ENCRYPTION.md).

## modulobias

To ensure the even distribution of the calc.pw password encoding, the script [`modulobias/src/modulobias.php`](https://github.com/yahesh/calcpw.docs/blob/master/modulobias/src/modulobias.php) has been developed and a modulo bias run has been executed against [calcpw.php](https://github.com/yahesh/calcpw.php). The results can be found in the file [`modulobias/dist/calcpw.txt`](https://github.com/yahesh/calcpw.docs/blob/master/modulobias/dist/calcpw.txt). The modulo bias run was executed with the following command and version **v0.1b0** of the script, so the results should be reproducible:

```
$ ./calcpw.php --modulobias "password" "information" "0-9 A-Z a-z" | ./modulobias.php
```

The calc.pw password encoding has passed the modulo bias test.