<?php

namespace App\Enums;

enum RatingDirection: string
{
    case PosterToStudent = 'poster_to_student';
    case StudentToPoster = 'student_to_poster';
}
