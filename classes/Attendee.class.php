<?php
  /**
  * Attendee
  * 
  * Class to incapsulate attendee-related data and logic
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */
  class Attendee{
      protected $name;
      protected $workingHoursStart;
      protected $workingHoursStop;
      protected $tz;
      
      /**
      * Attendee's constructor
      * 
      * @param string $name attendee's full name
      * @param string $workingHoursStart working hours start, i.e. 08:00AM
      * @param string $workingHoursStop working hours stop, i.e. 07:00PM
      * @param string $tz attendee's time xone
      * @return Attendee
      */
      public function __construct($name, $workingHoursStart, $workingHoursStop, $tz){
          $this->name = $name;
          
          if(preg_match('/^(\d{2}:\d{2})(AM|PM)$/', $workingHoursStart, $matches)){
              $this->workingHoursStart = $workingHoursStart;
          }
          else
            throw new Exception(sprinf("Incorrect working hour start format for %s: %s", $name, $workingHoursStart));
          
          if(preg_match('/^(\d{2}:\d{2})(AM|PM)$/', $workingHoursStop, $matches)){
              $this->workingHoursStop = $workingHoursStop; 
          }
          else
            throw new Exception(sprintf("Incorrect working hour stop format for %s: %s", $name, $workingHoursStop));
            
          $this->tz = $tz;
      }
      
      /**
      * Get time attendee starts work on some particular date
      * 
      * @param DateTime $today today's date
      * @return DateTime
      */
      public function getTodaysStart(DateTime $today){
          $dtObj = DateTime::createFromFormat('Y-m-d h:iA', $today->format('Y-m-d')." ".$this->workingHoursStart, new DateTimeZone($this->tz));
          return $dtObj; 
      }
      
      /**
      * Get time attendee stops work on some particular date
      * 
      * @param DateTime $today today's date
      * @return DateTime
      */
      public function getTodaysStop(DateTime $today){
          $dtObj = DateTime::createFromFormat('Y-m-d h:iA', $today->format('Y-m-d')." ".$this->workingHoursStop, new DateTimeZone($this->tz));
          return $dtObj;
      }
      
      /**
      * Class getter function
      * 
      * @param string $name
      */
      function __get($name) {
          return $this->$name;
      }
  }