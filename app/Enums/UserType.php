<?php

namespace App\Enums;

enum UserType: string
{
    case Student = 'student';
    case FacultyStaff = 'faculty_staff';
    case Organization = 'organization';
}
