<?php namespace MaddHatter\LaravelFullcalendar;
use Illuminate\Support\Collection;
use ArrayAccess;
use DateTime;
use Illuminate\View\Factory;
use App\Conversation;
class Calendar
{

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var EventCollection
     */
    protected $eventCollection;

    /**
     * @var string
     */
    protected $id;

     protected $event;

     protected $conversationCollection;

    /**
     * Default options array
     *
     * @var array
     */
    protected $defaultOptions = [
        

         'plugins'=> [ 'interaction', 'dayGrid', 'timeGrid' ],
'selectable'=> true,
'weekends'=>true,
'header' => [
            'left' => 'prev,next,today',
            'center' => 'title',
            'right' => 'month,agendaWeek,agendaDay',
        ],


    ];

    /**
     * User defined options
     *
     * @var array
     */
    protected $userOptions = [];

    /**
     * User defined callback options
     *
     * @var array
     */
    protected $callbacks = [
        'dayClick'=> 'function(date, jsEvent, view) {
            document.getElementById("new_event_start_date").value = date.format();
            document.getElementById("new_event_end_date").value = date.format();
    $("#create-event").modal("toggle");

 
    }',
'eventClick'=> 'function(calEvent, jsEvent, view) {
 document.getElementById("newconversationdate").value = calEvent.start.format();
 document.getElementById("newconversationeventid").value = calEvent.id;

   $("#show-event").modal("toggle");
$("#messageEvent").empty();
 $("#messageEvent").append("<h4>Kayıtlı Görüşmeler : </h4><hr>");
calEvent.conversations.forEach(function(item) {


 $("#messageEvent").append("<div style="+"background-color:#BFEE90;padding:10px;border-radius: 4px;"+"><small>"+item.event_date+"/"+item.user.name+"</small><p>"+item.details+"</p></div>");


 $("#messageEvent").append("<br>");

});


     
  

  }'];

    /**
     * @param Factory         $view
     * @param EventCollection $eventCollection
     */
    public function __construct(Factory $view, EventCollection $eventCollection)
    {
        $this->view            = $view;
        $this->eventCollection = $eventCollection;

    }

    /**
     * Create an event DTO to add to a calendar
     *
     * @param string          $title
     * @param string          $isAllDay
     * @param string|DateTime $start If string, must be valid datetime format: http://bit.ly/1z7QWbg
     * @param string|DateTime $end   If string, must be valid datetime format: http://bit.ly/1z7QWbg
     * @param string          $id    event Id
     * @param array           $options
     * @return SimpleEvent
     */
    public static function event($title, $isAllDay, $start, $end, $conversations, $user,$id = null, $options = [])

    {

        return new SimpleEvent($title, $isAllDay, $start, $end, $conversations, $user,$id, $options);
    }

    /**
     * Create the <div> the calendar will be rendered into
     *
     * @return string
     */
    public function calendar()
    {
        return '<div id="calendar-' . $this->getId() . '"></div>';
    }

    /**
     * Get the <script> block to render the calendar (as a View)
     *
     * @return \Illuminate\View\View
     */
    public function script()
    {
        $options = $this->getOptionsJson();

        return $this->view->make('fullcalendar::script', [
            'id' => $this->getId(),
            'options' => $options,
        ]);
    }

    /**
     * Customize the ID of the generated <div>
     *
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the ID of the generated <div>
     * This value is randomized unless a custom value was set via setId
     *
     * @return string
     */
    public function getId()
    {
        if ( ! empty($this->id)) {
            return $this->id;
        }

        $this->id = str_random(8);

        return $this->id;
    }

    /**
     * Add an event
     *
     * @param Event $event
     * @param array $customAttributes
     * @return $this
     */
    public function addEvent(Event $event, array $customAttributes = [])
    {
        $this->eventCollection->push($event, $customAttributes);

        return $this;
    }
public function getEvent($trg)
    {
        $this->event=$this->eventCollection[$trg];

        
    }
    /**
     * Add multiple events
     *
     * @param array|ArrayAccess $events
     * @param array $customAttributes
     * @return $this
     */
    public function addEvents($events, array $customAttributes = [])
    {
        foreach ($events as $event) {
            $this->eventCollection->push($event, $customAttributes);
            
        }

        return $this;
    }
 public function viewEvents()
    {
        

        return $this->eventCollection;
    }

    /**
     * Set fullcalendar options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->userOptions = $options;

        return $this;
    }

    /**
     * Get the fullcalendar options (not including the events list)
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge($this->defaultOptions, $this->userOptions);
    }

    /**
     * Set fullcalendar callback options
     *
     * @param array $callbacks
     * @return $this
     */
    public function setCallbacks(array $callbacks)
    {
        $this->callbacks = $callbacks;

        return $this;
    }

    /**
     * Get the callbacks currently defined
     *
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * Get options+events JSON
     *
     * @return string
     */
    public function getOptionsJson()
    {
        $options      = $this->getOptions();
        $placeholders = $this->getCallbackPlaceholders();
        $parameters   = array_merge($options, $placeholders);

        // Allow the user to override the events list with a url
        if (!isset($parameters['events'])) {
            $parameters['events'] = $this->eventCollection->toArray();
        }

        $json = json_encode($parameters);

        if ($placeholders) {
            return $this->replaceCallbackPlaceholders($json, $placeholders);
        }

        return $json;

    }

    /**
     * Generate placeholders for callbacks, will be replaced after JSON encoding
     *
     * @return array
     */
    protected function getCallbackPlaceholders()
    {
        $callbacks    = $this->getCallbacks();
        $placeholders = [];

        foreach ($callbacks as $name => $callback) {
            $placeholders[$name] = '[' . md5($callback) . ']';
        }

        return $placeholders;
    }

    /**
     * Replace placeholders with non-JSON encoded values
     *
     * @param $json
     * @param $placeholders
     * @return string
     */
    protected function replaceCallbackPlaceholders($json, $placeholders)
    {
        $search  = [];
        $replace = [];

        foreach ($placeholders as $name => $placeholder) {
            $search[]  = '"' . $placeholder . '"';
            $replace[] = $this->getCallbacks()[$name];
        }

        return str_replace($search, $replace, $json);
    }

}

