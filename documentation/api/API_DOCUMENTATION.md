# UTB Rubrics Web Service API Documentation

This document describes the Web Service API for the UTB Rubrics grading plugin.

## Overview

The UTB Rubrics API provides secure access to evaluation data and grading structure information. Access is restricted to **teachers and administrators only**.

## Security & Permissions

### Web Service Setup

**Important**: This API requires a token from the dedicated **"UTB Rubrics Web Service"**. Tokens from other services (e.g., Moodle Mobile Web Service) will NOT work.

To create a token:
1. Go to: Site administration → Server → Web services → Manage tokens
2. Click "Add"
3. Select a user (must be teacher or administrator)
4. **Service**: Select **"UTB Rubrics Web Service"**
5. Save and copy the generated token

### Required Capabilities

Users must have at least ONE of the following capabilities:
- `mod/assign:grade` - Permission to grade assignments
- `moodle/grade:viewall` - Permission to view all grades
- `gradereport/grader:view` - Permission to view grader report

### Authentication

All API calls require:
1. Valid token from **UTB Rubrics Web Service** (not other services)
2. Proper capability permissions
3. Valid context (course or assignment for evaluations)

## Available Functions

### 1. Get Evaluations

Retrieve evaluation data from the UTB Rubrics grading table.

**Function Name:** `gradingform_utbrubrics_get_evaluations`

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| courseid | int | No | Filter by course ID |
| assignmentid | int | No | Filter by assignment ID |
| graderid | int | No | Filter by grader user ID |
| studentid | int | No | Filter by student user ID |

#### Response Structure

```json
{
  "evaluations": [
    {
      "id": 123,
      "instanceid": 456,
      "indicator_id": 15,
      "indicator_letter": "A",
      "indicator_description_en": "Ability to identify and formulate problems",
      "indicator_description_es": "Capacidad para identificar y formular problemas",
      "student_outcome_id": 1,
      "so_number": "s01",
      "so_title_en": "Problem Analysis",
      "so_title_es": "Análisis de Problemas",
      "performance_level_id": 3,
      "performance_level_name_en": "Excellent",
      "performance_level_name_es": "Excelente",
      "min_score": 3.6,
      "max_score": 4.0,
      "score": 3.8,
      "feedback": "Good work on problem identification",
      "course_id": 5,
      "course_name": "Introduction to Engineering",
      "activity_name": "Final Project",
      "assignment_id": 10,
      "student_id": 20,
      "student_name": "Jane Doe",
      "grader_id": 5,
      "grader_name": "Prof. Smith",
      "rubric_name": "UTB Rubrics",
      "timecreated": 1698765432,
      "timemodified": 1698765500
    }
  ],
  "count": 1
}
```

#### Usage Example (JavaScript)

```javascript
// Using Moodle's ajax module
require(['core/ajax'], function(ajax) {
    var promises = ajax.call([{
        methodname: 'gradingform_utbrubrics_get_evaluations',
        args: {
            courseid: 5,
            assignmentid: 10
        }
    }]);
    
    promises[0].done(function(response) {
        console.log('Found ' + response.count + ' evaluations');
        response.evaluations.forEach(function(eval) {
            console.log('Indicator: ' + eval.indicator_letter + 
                       ', Score: ' + eval.score);
        });
    }).fail(function(error) {
        console.error('Error:', error);
    });
});
```

#### cURL Example

```bash
curl -X POST 'https://yourmoodle.com/webservice/rest/server.php' \
  -d 'wstoken=YOUR_TOKEN_HERE' \
  -d 'wsfunction=gradingform_utbrubrics_get_evaluations' \
  -d 'moodlewsrestformat=json' \
  -d 'courseid=5' \
  -d 'assignmentid=10'
```

---

### 2. Get Student Outcomes

Retrieve the complete Student Outcomes structure including indicators and performance levels. Student Outcomes are universal and not course-specific.

**Function Name:** `gradingform_utbrubrics_get_student_outcomes`

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| lang | string | No | en | Language code ('en' or 'es') |

#### Response Structure

```json
{
  "student_outcomes": [
    {
      "id": 1,
      "so_number": "SO1",
      "title": "Problem Analysis",
      "description": "An ability to identify, formulate, and solve complex engineering problems",
      "indicators": [
        {
          "id": 15,
          "letter": "A",
          "description": "Identifies and formulates engineering problems",
          "performance_levels": [
            {
              "id": 45,
              "name": "Excellent",
              "definition": "Accurately identifies all aspects of the problem",
              "min_score": 3.6,
              "max_score": 4.0
            },
            {
              "id": 46,
              "name": "Good",
              "definition": "Identifies most aspects of the problem",
              "min_score": 2.8,
              "max_score": 3.5
            }
          ]
        }
      ]
    }
  ],
  "count": 7,
  "language": "en"
}
```

#### Usage Example (JavaScript)

```javascript
require(['core/ajax'], function(ajax) {
    var promises = ajax.call([{
        methodname: 'gradingform_utbrubrics_get_student_outcomes',
        args: {
            lang: 'en'
        }
    }]);
    
    promises[0].done(function(response) {
        console.log('Retrieved ' + response.count + ' Student Outcomes');
        response.student_outcomes.forEach(function(so) {
            console.log('SO: ' + so.so_number + ' - ' + so.title);
            console.log('Indicators: ' + so.indicators.length);
        });
    }).fail(function(error) {
        console.error('Error:', error);
    });
});
```

#### cURL Example

```bash
curl -X POST 'https://yourmoodle.com/webservice/rest/server.php' \
  -d 'wstoken=YOUR_TOKEN_HERE' \
  -d 'wsfunction=gradingform_utbrubrics_get_student_outcomes' \
  -d 'moodlewsrestformat=json' \
  -d 'lang=es'
```

---

## Setup Instructions

### 1. Enable Web Services

1. Navigate to: **Site administration → Advanced features**
2. Enable "Enable web services"
3. Save changes

### 2. Enable REST Protocol

1. Navigate to: **Site administration → Server → Web services → Manage protocols**
2. Enable "REST protocol"

### 3. Create a Service (Optional)

The plugin automatically registers the service "UTB Rubrics Web Service" with shortname `utbrubrics_ws`.

To use it:
1. Navigate to: **Site administration → Server → Web services → External services**
2. Find "UTB Rubrics Web Service"
3. Click "Authorised users"
4. Add users who should have API access

### 4. Generate User Token

1. Navigate to: **Site administration → Server → Web services → Manage tokens**
2. Click "Add"
3. Select a user (must have teacher or admin role)
4. Select the service "UTB Rubrics Web Service"
6. Copy the generated token

---

## Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `nopermissions` | User lacks required capability | Grant `mod/assign:grade` or `moodle/grade:viewall` |
| `invalidtoken` | Token is invalid or expired | Generate a new token |
| `invalidparameter` | Invalid parameter type or value | Check parameter format |
| `apiaccessdenied` | User is not teacher/admin | Only teachers and admins can use this API |

### Error Response Format

```json
{
  "exception": "moodle_exception",
  "errorcode": "nopermissions",
  "message": "Access denied: Only teachers and administrators can access this API"
}
```

---

## Best Practices

1. **Token Security**
   - Never expose tokens in client-side code
   - Store tokens securely on the server
   - Rotate tokens periodically

2. **Performance**
   - Use filters to limit result sets
   - Cache responses when appropriate
   - Implement pagination for large datasets

3. **Language Handling**
   - Always specify language explicitly
   - Default is 'en' if not specified
   - Only 'en' and 'es' are supported

4. **Context Awareness**
   - Provide courseid when filtering by course
   - Provide assignmentid for assignment-specific data
   - Use appropriate context for permission checks

---