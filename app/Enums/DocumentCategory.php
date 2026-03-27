<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case API_DOCUMENTATION = 'api_documentation';
    case ARCHITECTURE = 'architecture';
    case INTEGRATION_GUIDE = 'integration_guide';
    case GENERAL_NOTES = 'general_notes';
}
