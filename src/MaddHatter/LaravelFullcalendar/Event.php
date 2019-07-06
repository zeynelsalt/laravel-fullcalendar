<?php namespace MaddHatter\LaravelFullcalendar;

use DateTime;
 use Illuminate\Support\Collection;
use App\Conversation;
interface Event
{
    /**
     * Get the event's title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Is it an all day event?
     *
     * @return bool
     */
    public function isAllDay();

    /**
     * Get the start time
     *
     * @return DateTime
     */
    public function getStart();

    /**
     * Get the end time
     *
     * @return DateTime
     */
    public function getEnd();
/**
     * Get conversations
     *
     * @return Conversation
     */
    public function getConversations();

    public function getUser();

}
