<?php
  /**
  * Time Slot
  * 
  * Class to incapsulate time-slot related data and logic
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */
  
  class TimeSlot{
      protected $start;
      protected $stop;
      protected $notAvailableFor = array();
      protected $availableFor = array();
      
      /**
      * TimeSlot constructor
      * 
      * @param DateTime $start time slot start time
      * @param DateTime $stop time slot end time
      * @return TimeSlot
      */
      public function __construct(DateTime $start, DateTime $stop){
          $this->start = $start;
          $this->stop = $stop;
      }
      
      /**
      * Invalidates current time slot for particular invitee
      * 
      * @param Attendee $attendee invitee that already has an appointment for this time
      * @param string $reason reason why this time slot is not usable for invitee
      */
      public function invalidateFor(Attendee $attendee, $reason){
          $this->notAvailableFor[$attendee->name] = array('attendee' => $attendee->name, 'reason' => $reason);
          unset($this->availableFor[$attendee->name]);
      }
      
      /**
      * Put invitees to list of available invitees
      * 
      * @param array $attenddes
      */
      public function setAllAttendeesValid($attenddes){
          if(is_array($attenddes) && !empty($attenddes)){
              foreach($attenddes as $attendee){
                  $this->availableFor[$attendee->name] = 1;
              }
          }
      }
      
      /**
      * Get number of ivitees that can't use this slot for appointment
      * 
      * @return integer
      */
      public function getNumberOfIssues(){
          return count($this->notAvailableFor);
      }
      
      /**
      * Returns true is current time slot is available for all
      * 
      * @return bool
      */
      public function isAvailable(){
          return count($this->notAvailableFor) == 0;
      }
      
      /**
      * Class getter function
      * 
      * @param string $name
      */
      function __get($name) 
      {
            return $this->$name;
      }
  }