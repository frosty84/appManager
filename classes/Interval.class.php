<?php
  /**
  * Interval
  * 
  * Abstract helper class with interval descriptions
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */
  abstract class Interval{
      protected static $INTERVALS = array('s' => 'seconds', 'm' => 'minutes', 'h' => 'hours', 'd' => 'days');
  }