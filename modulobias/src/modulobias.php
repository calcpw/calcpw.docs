#!/usr/bin/env php
<?php

  # modulobias.php v0.1b0
  #
  # Copyright (c) 2022-2024, Yahe
  # All rights reserved.
  #
  #
  # usage:
  # ======
  #
  # ./modulobias.php
  #
  #
  # description:
  # ============
  #
  # The script reads data from STDIN, counts characters and checks for a modulo bias.
  #
  #
  # execution:
  # ==========
  #
  # To execute the script you have to call it in the following way:
  #
  # ./modulobias.php

  # define how many characters shall be read
  define("DATASET", 1024*1024);

  # define when a bias is visible
  define("THRESHOLD", 0.0005);

  # ===== DO NOT EDIT HERE =====

  function println($text) {
    print($text.PHP_EOL);
  }

  function main($arguments) {
    $result = 0;

    $char    = false;
    $counter = 0;
    $dataset = [];
    for ($i = 0; $i < DATASET; $i++) {
      $char = fgetc(STDIN);
      if (false !== $char) {
        # increment counter
        $counter++;

        if (!array_key_exists($char, $dataset)) {
          # initialize data entry
          $dataset[$char] = 1;
        } else {
          # increment data entry
          $dataset[$char]++;
        }
      } else {
        break;
      }
    }

    # sort the dataset by value but keep keys
    arsort($dataset, SORT_NUMERIC);

    # try to identify a modulo bias
    $is_biased = false;
    $unbiased  = 1 / count($dataset);

    println("INFO: threshold bias towards = ".($unbiased + THRESHOLD));
    println("INFO: unbiased distribution  = ".$unbiased);
    println("INFO: threshold bias against = ".($unbiased - THRESHOLD));
    println("");

    foreach ($dataset as $key => $value) {
      $distribution = $value / $counter;
      $bias         = $unbiased - $distribution;

      # check if we found a bias
      if (THRESHOLD < abs($bias)) {
        # we identified a bias
        $is_biased = true;

        if (0 < $bias) {
          println("$key = $distribution (BIASED AGAINST)");
        } else {
          println("$key = $distribution (BIASED TOWARDS)");
        }
      } else {
        println("$key = $distribution");
      }
    }

    if (!$is_biased) {
      println("");
      println("INFO: the provided character distribution is unbiased");
    } else {
      println("");
      println("ERROR: the provided character distribution is biased");

      $result = 1;
    }

    if (false === $char) {
      println("");
      println("ERROR: STDIN did not provide the required number of characters");
      
      $result = 2;
    }

    return $result;
  }

  exit(main($argv));

