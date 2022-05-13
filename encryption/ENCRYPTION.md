# calc.pw Password Calculation

This document is meant to describe the rationale behind the **calc.pw password calculation** algorithm. The calc.pw password calculation consists of two steps - namely, the **calc.pw key expansion** and the **calc.pw password encoding** - which will be discussed separately.

## calc.pw Key Expansion

The calc.pw key expansion can be described as a salted password-based key derivation function whereby a single strong **password** is combined with a service-dependent individual **information** to generate a stream of pseudo-random bytes. For each service the same **password** is used while the **information** changes.

![calc.pw key expansion](https://github.com/yahesh/calcpw.docs/raw/main/drawio/dist/calcpw.png)

### Key derivation

The key derivation is done using [PBKDF2](https://www.ietf.org/rfc/rfc2898.txt) with [SHA-256](https://nvlpubs.nist.gov/nistpubs/FIPS/NIST.FIPS.180-4.pdf) as the hash algorithm, the **information** as the salt, the **password** and an iteration count that will depend on the computing power of the designated hardware (the [Raspberry Pi Pico](https://www.raspberrypi.com/products/raspberry-pi-pico/specifications/) with an [RP2040](https://www.raspberrypi.com/documentation/microcontrollers/rp2040.html) microcontroller). The result is a 32 byte pseudo-random key.

### Counter derivation

The counter derivation is done using [AES-256](https://nvlpubs.nist.gov/nistpubs/FIPS/NIST.FIPS.197.pdf)-ECB as the encryption algorithm, `0x00000000000000000000000000000000` as the plaintext and the value derived during the [key derivation](#key-derivation) as the key. The result is a 16 byte pseudo-random nonce.

### Randomness extraction

The extraction of the stream of pseudo-random bytes is done using [AES-256](https://nvlpubs.nist.gov/nistpubs/FIPS/NIST.FIPS.197.pdf)-CTR as the encryption algorithm, the value derived during the [counter derivation](#counter-derivation) as the nonce and the value derived during the [key derivation](#key-derivation) as the key. The result is a block of 16 bytes pseudo-random data for each round.

### Rationale

The following factors have contributed to the selection of the described key expansion algorithm:

1. PBKDF2-SHA-256 was chosen because the calc.pw key expansion will be implemented on a microcontroller with limited CPU and RAM resources. PBKDF2 and SHA-256 are well-established, including implementations that are optimized for use on microcontrollers. The reduced memory constraints of PBKDF2 that are the basis for attacks against the scheme are required to implement the algorithm on a microcontroller.

2. Counter derivation was chosen to further mask the input of the randomness extraction. Feeding the ciphertext of one blockcipher round into the following round is an established mechanism to mask a plaintext (see the improvement of CBC over ECB).

3. Randomness extraction using AES-256-CTR was chosen over just using PBKDF2-SHA-256 for the whole key expansion to introduce a second barrier, so that an attacker that wants to learn the initial **password** has to breach or bruteforce PBKDF2-SHA-256 as well as AES-256-CTR. Using AES-256-CTR as a CSPRNG is established in general (see [CTR_DRBG](https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-90Ar1.pdf)). However, due to the small amount of pseudo-random bytes that are drawn from the randomness extraction, a re-keying construction has not been considered.

4. The idea of calc.pw is to strengthen password usage without having to use a password database. Users only have to remember one strong **password** but do not have to be able to implement a proper backup scheme for a password database. Given the same **password** and service-dependent **information** (that may be derived from a URL, a username or similar) the service-specific passwords can be re-calculated.

### Verification

To ensure the randomness distribution of the calc.pw key expansion, a full [dieharder](https://webhome.phy.duke.edu/~rgb/General/dieharder.php) run has been executed against [calcpw.php](https://github.com/yahesh/calcpw.php). The results can be found in the file [`dieharder/dist/calcpw.txt`](https://github.com/yahesh/calcpw.docs/blob/master/dieharder/dist/calcpw.txt). The dieharder run was executed with the following command and version **3.31.1** of dieharder, so the results should be reproducible:

```
$ ./calcpw.php --dieharder "password" "information" | dieharder -g 200 -a
```

The calc.pw key expansion has passed all dieharder tests. However the `sts_serial (ntuple = 6)`, `rgb_bitdist (ntuple = 11)` and `dab_filltree (ntuple = 32)` tests have only been passed as **weak** which may happen for PRNGs.

## calc.pw Password Encoding

The calc.pw password encoding consumes pseudo-random bytes extracted from the [calc.pw key expansion](#calcpw-key-expansion) and converts them to characters as specified by a given **character set**.

### Definition

A character set is defined as a collection of single characters and character ranges (e.g. `0-9` or `A-Z` or `a-z`). A character set consists of one or more character groups (e.g. `A-Za-z`) which are taken into account during the [enforcement](#enforcement).

**Examples:**

```
# character sets:
1.) xyzxyz 012 zyx012
2.) 012 xyz 210xyz012
3.) 0x1y2z 210210 zyx
```

### Normalization

A character set is normalized so that the reproducibility of the calc.pw password calculation is improved. The characters **within** the character groups are sorted by their ISO 8859-1 value and de-duplicated. Afterwards the character groups themselves are sorted by the ISO 8859-1 values of the characters they contain. Character groups containing the same characters are sorted by their character count from low to high.

#### Examples

```
# denormalized character sets:
1.) xyzxyz 012 zyx012
2.) 012 xyz 210xyz012
3.) 0x1y2z 210210 zyx

# normalized character sets:
1.) 012 012xyz xyz
2.) 012 012xyz xyz
3.) 012 012xyz xyz
```

### Flattening

A character set is flattened so that the reproducibility of the calc.pw password calculation is improved. Furthermore, flattening allows the character set to be represented as a one-dimensional array, leading to time-constant element access. The characters **within** the character groups are put into a single character group. Then they are sorted by their ISO 8859-1 value and de-duplicated.

#### Examples:

```
# normalized character sets:
1.) 012 012xyz xyz
2.) 012 012xyz xyz
3.) 012 012xyz xyz

# flattened character sets:
1.) 012xyz
2.) 012xyz
3.) 012xyz
```

### Encoding

The encoding of an extracted pseudo-random byte of the calc.pw key expansion takes place through the following command, where `$char` is the encoded character, `$byte` is the extracted pseudo-random byte, `$charset[]` is an array containing the flattened character set, `sizeof($charset)` is the number of characters contained in the flattened character set and `floor()` returns the integer part of a float.

```
if ($byte < (floor(256 / sizeof($charset))) * sizeof($charset)) {
  $char = $charset[$byte mod sizeof($charset)]
}
```

### Enforcement

A character set is enforced so that a character of each character group is contained in the calculated password as to meet password requirements that exist in practice. If a calculated password does not meet the character group requirements, then the password is rejected and a new password is generated by continuing the key expansion and password encoding process. This continues until a fitting password has been calculated.

### Rationale

The following factors have contributed to the selection of the described password encoding algorithm:

1. Oftentimes, passwords do not have to meet specific password requirements aside from a minimum length. However, sometimes more strict requirements have to be met. Therefore, it is necessary to allow the user to define which characters a calculated password may contain as well as to allow the user to enforce certain character groups to be contained.

2. calc.pw will have a keypad-based interface. To improve the reproducibility of the password encoding, a given character set is normalized and flattened to prevent different entries of the character set by the user from producing different passwords as far as possible.

3. Limiting characters to the ISO 8859-1 character encoding is sufficient **for now** as calc.pw will have a keybad-based interface and thus will have to be limited to a restricted character set anyway.

4. As random values are mapped to a restricted character set, rejection sampling is used during the encoding to prevent modulo bias. Another possibility would have been to Base64-encode the pseudo-random bytes, drop the special characters (`/` and `+`) and introduce required characters afterwards. However, using a user-defined character set and using rejection sampling as well as enforcement was considered to be a cleaner implementation.

### Verification

To ensure the even distribution of the calc.pw password encoding, the script [`modulobias/src/modulobias.php`](https://github.com/yahesh/calcpw.docs/blob/master/modulobias/src/modulobias.php) has been developed and a modulo bias run has been executed against [calcpw.php](https://github.com/yahesh/calcpw.php). The results can be found in the file [`modulobias/dist/calcpw.txt`](https://github.com/yahesh/calcpw.docs/blob/master/modulobias/dist/calcpw.txt). The modulo bias run was executed with the following command and version **v0.1b0** of the script, so the results should be reproducible:

```
$ ./calcpw.php --modulobias "password" "information" "0-9 A-Z a-z" | ./modulobias.php
```

The calc.pw password encoding has passed the modulo bias test.
