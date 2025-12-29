<?php
enum JobState
{
    case PENDING;
    case RUNNING;
    case COMPLETED;
    case FAILED;
}