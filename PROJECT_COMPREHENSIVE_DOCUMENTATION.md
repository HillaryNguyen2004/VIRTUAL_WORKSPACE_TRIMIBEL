# DO_AN_CHUYEN_NGANH - Comprehensive Project Documentation

## Table of Contents

1. [Project Overview](#project-overview)
2. [What It Is](#what-it-is)
3. [System Architecture](#system-architecture)
4. [Core Technologies](#core-technologies)
5. [Functional Features & Modules](#functional-features--modules)
6. [How It Works](#how-it-works)
7. [Team Responsibilities](#team-responsibilities)
8. [Results & Outputs](#results--outputs)
9. [Extensions & Enhancements](#extensions--enhancements)
10. [Project Setup & Deployment](#project-setup--deployment)

---

## Project Overview

**Project Name:** DO_AN_CHUYEN_NGANH (Specialized Capstone Project)  
**Type:** Enterprise HR Management System with AI/ML Integration  
**Status:** Active Development  
**Team Size:** 4 members  
**Repository:** HillaryNguyen2004/DO_AN_CHUYEN_NGANH  

---

## What It Is

This is an **integrated Human Resource Management Platform** designed to modernize employee management and productivity tracking for organizations. The system combines:

- **Real-time HR Operations**: User management, project tracking, task assignment, attendance monitoring
- **AI-Powered Insights**: Next-day productivity forecasting using LSTM neural networks
- **Intelligent Chatbot**: RAG-based conversational AI for document Q&A and productivity analysis
- **Data Analytics**: ETL pipeline processing operational data into actionable insights
- **Collaborative Tools**: Real-time chat, document management, whiteboard, calendar integration

**Core Value Proposition:**
- Predict employee productivity **before it happens** rather than analyzing historical data
- Enable proactive HR interventions with AI-powered recommendations
- Centralize all HR workflows in one intelligent platform
- Provide data-driven insights through advanced analytics

---

## System Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                          CLIENT LAYER                              │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Web Browser (React/Vue + Vite + TailwindCSS)                │  │
│  │ - Dashboard & Reports                                         │  │
│  │ - Project Management UI                                       │  │
│  │ - Task Tracking Interface                                     │  │
│  │ - Chat & Notifications                                        │  │
│  │ - LSTM Predictions Dashboard                                  │  │
│  └──────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌────────────────────────────────────────────────────────────────────┐
│                        API LAYER (Laravel)                         │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ RESTful API Endpoints (PHP-8.2, Laravel 9.19)                │  │
│  │ ┌────────────────────────────────────────────────────────┐  │  │
│  │ │ Controllers: ProjectController, TaskController,        │  │  │
│  │ │ UserController, LSTMDashboardController, etc.          │  │  │
│  │ │ Services: ProjectService, TaskService, etc.             │  │  │
│  │ │ Models: User, Project, Task, CheckIn, DayOff, etc.     │  │  │
│  │ └────────────────────────────────────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────┘
        │                │                  │              │
        ▼                ▼                  ▼              ▼
   ┌─────────┐    ┌───────────┐    ┌──────────────┐  ┌─────────┐
   │ MySQL   │    │PostgreSQL │    │   Flask API  │  │  Ollama │
   │(Ops)    │    │(DW)       │    │(ML Service)  │  │(LLM)    │
   └─────────┘    └───────────┘    └──────────────┘  └─────────┘
        │               │                  │              │
        ▼               ▼                  ▼              ▼
   ┌────────────────────────────────────────────────────────────┐
   │              BACKEND SERVICES LAYER                        │
   │  ┌──────────────────────────────────────────────────────┐ │
   │  │ ETL Pipeline: MySQL → PostgreSQL                     │ │
   │  │ - Data Warehouse Schema (Dimensional Model)          │ │
   │  │ - 6 Dimension Tables + 1 Fact Table                  │ │
   │  │ - Productivity Score Calculation                     │ │
   │  └──────────────────────────────────────────────────────┘ │
   │  ┌──────────────────────────────────────────────────────┐ │
   │  │ ML/LSTM Pipeline: Flask API (Port 5001)              │ │
   │  │ - Productivity Forecasting (Next 24 hours)           │ │
   │  │ - Model Artifacts (KERAS, Scaler, Baseline)         │ │
   │  │ - Endpoints: /predict/{id}, POST /predict/all        │ │
   │  └──────────────────────────────────────────────────────┘ │
   │  ┌──────────────────────────────────────────────────────┐ │
   │  │ RAG Chatbot Service: FastAPI (Port 8000)             │ │
   │  │ - Document Ingestion (PDF, DOCX, CSV, TXT, etc.)    │ │
   │  │ - Vector Storage (ChromaDB)                          │ │
   │  │ - Ollama Embeddings & Generation                     │ │
   │  │ - Multi-language Support                             │ │
   │  └──────────────────────────────────────────────────────┘ │
   └────────────────────────────────────────────────────────────┘
```

---

## Core Technologies

### Backend Stack
| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| **Framework** | Laravel | 9.19 | Web application framework |
| **PHP** | PHP | 8.2+ | Server-side language |
| **Database (Ops)** | MySQL | 5.7+ | Operational data store |
| **Database (Analytics)** | PostgreSQL | 12+ | Data warehouse |
| **API Auth** | JWT + Sanctum | 3.0 | Authentication & authorization |
| **Queue** | Redis/Pusher | 7.2 | Real-time messaging |
| **File Storage** | AWS S3 | 3.32 | Cloud file storage |

### Frontend Stack
| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Build Tool** | Vite | Fast bundler and dev server |
| **Styling** | TailwindCSS | Utility-first CSS framework |
| **UI Components** | FontAwesome | Icon library |
| **Editor** | TipTap | Rich text editor |
| **Charts** | Chart.js | Data visualization |
| **Rich Sheets** | LuckySheet | Excel-like spreadsheet |

### AI/ML Stack
| Component | Technology | Purpose |
|-----------|-----------|---------|
| **ML Framework** | TensorFlow/Keras | Neural network training |
| **ML Language** | Python 3.9+ | Data science implementation |
| **ML API** | Flask | Model serving (port 5001) |
| **LLM** | Ollama | Open-source language model |
| **RAG API** | FastAPI | Vector-based search service |
| **Vector DB** | ChromaDB | Persistent embeddings storage |
| **Data Warehouse** | PostgreSQL | Analytical data store |

### DevOps & Deployment
| Component | Technology |
|-----------|-----------|
| **Environment** | Docker/Composable |
| **API Gateway** | Vercel (Serverless option) |
| **CDN** | Cloudflare |
| **Package Manager** | Composer (PHP), npm/yarn (JS) |
| **Testing** | PHPUnit |

---

## Functional Features & Modules

### 1. **User & Role Management**
**Owner:** Khoa & Nguyen  
**Description:** Complete user lifecycle management with role-based access control (RBAC)

**Features:**
- User profile creation, editing, and deletion
- Role assignment (Admin, Staff, Regular User)
- Department-based permission hierarchy
- Permission validation and enforcement
- User authentication via JWT/Sanctum
- Social login integration (Google, Facebook via Socialite)

**Endpoints:**
- `GET /api/users` - List all users
- `POST /api/users` - Create user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `GET /api/users/{id}/permissions` - Get user permissions

**Database Tables:**
- `users` - User accounts
- `roles` - Role definitions
- `permissions` - Permission definitions
- `user_roles` - User-role assignments

---

### 2. **Workspace Management**
**Owner:** Khoa  
**Description:** Virtual workspaces for team organization and collaboration

**Features:**
- Create and manage workspaces
- Invite users to workspaces
- Workspace hierarchy and nesting
- Role-based access within workspaces
- Workspace-specific settings and configurations

**Database Tables:**
- `workspaces` - Workspace entities
- `workspace_users` - Workspace membership
- `workspace_settings` - Workspace configuration

---

### 3. **Project Management**
**Owner:** Khoa  
**Description:** Full project lifecycle management with phases and tracking

**Features:**
- Create, read, update, delete (CRUD) projects
- Project phases with dates
- Project status tracking (Active, Inactive, Completed)
- Assignment to staff/team leaders
- Project progress percentage tracking
- Deadline management

**Endpoints:**
- `GET /api/projects` - List projects (paginated)
- `POST /api/projects` - Create project
- `GET /api/projects/{id}` - Get project details
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project
- `GET /api/projects/{id}/tasks` - Get project tasks

**Database Tables:**
- `projects` - Project records
- `phases` - Project phases
- `project_phases` - Phase-project relationships

**Business Rules:**
- Staff member must be validation before assignment
- Cannot delete project if active tasks exist
- Phase dates must be within project dates

---

### 4. **Task Management**
**Owner:** Khoa  
**Description:** Complete task tracking with assignment, progress, and quality scoring

**Features:**
- Task creation with title, description, dates
- Task assignment to single or multiple users
- Task status tracking (Not Started, In Progress, Completed, On Hold)
- Progress percentage tracking
- Task priority levels (Low, Medium, High, Critical)
- Estimated hours and actual hours logging
- Task quality scoring (0-10 scale)
- Subtask support (parent-child relationships)
- Task dependencies
- Timeline visualization

**Endpoints:**
- `GET /api/tasks` - List tasks (with filters)
- `POST /api/tasks` - Create task
- `GET /api/tasks/{id}` - Get task details
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task
- `PUT /api/tasks/{id}/status` - Update task status
- `GET /api/tasks/project/{projectId}` - Get project tasks

**Database Tables:**
- `tasks` - Task records
- `task_assignments` - User-task assignments
- `task_time_logs` - Time tracking entries

**Advanced Features:**
- Task burndown chart data
- Task velocity metrics
- Dependency chain resolution

---

### 5. **Attendance & Check-In System**
**Owner:** Khoa & Nguyen  
**Description:** Real-time attendance tracking with flexible hour logging

**Features:**
- Daily check-in/check-out logging
- Working hours calculation (HH:MM format)
- Late arrival tracking
- Automatic daily record creation
- Manual check-in adjustment by admin
- Holiday/day-off detection
- Attendance reports and analytics

**Database Tables:**
- `check_ins` - Daily check-in records
- `working_hours_config` - Company working hours setup
- `holidays` - Holiday calendar

**Data Points Captured:**
- `checked_in` (boolean)
- `checked_out` (boolean)
- `is_late` (boolean)
- `working_hours` (time)
- `timestamp` (datetime)

---

### 6. **Day-Off/Leave Request Management**
**Owner:** Tri  
**Description:** Leave request workflow with approval and tracking

**Features:**
- Leave type management (Vacation, Sick, Personal, etc.)
- Half-day and full-day request options
- Approval workflow (Request → Manager Review → Approved/Rejected)
- Leave balance tracking per employee
- Override for admin approval
- Audit trail of all requests
- Integration with productivity scoring (score = 0 on approved leave days)

**Database Tables:**
- `day_off_requests` - Leave requests
- `day_off_types` - Leave type definitions
- `leave_balance` - Per-user balances

**Request Statuses:**
- `pending` - Awaiting review
- `approved` - Accepted
- `rejected` - Denied
- `cancelled` - User-cancelled

---

### 7. **Department Management**
**Owner:** Kminh  
**Description:** Organizational hierarchy and department-level controls

**Features:**
- Create and manage departments
- Department hierarchy (parent-child relationships)
- Assign users to departments
- Department-wide permission assignment
- Department-based reporting
- Cost center tracking

**Database Tables:**
- `departments` - Department entities
- `department_permissions` - Department-level access control

**Advanced Features:**
- Multi-level department hierarchies
- User reassignment with history tracking
- Department metrics and KPIs

---

### 8. **Real-Time Chat System**
**Owner:** Kminh  
**Description:** Instant messaging between users and groups

**Features:**
- One-to-one direct messaging
- Group chat channels
- Message persistence and history
- Real-time delivery via WebSockets (Pusher)
- Message search and filtering
- User typing indicators
- Read receipts
- Message reactions (emoji)
- File attachment support

**Technologies:**
- Pusher (WebSocket support)
- Laravel Echo (client-side)
- Message queue for reliability

**Database Tables:**
- `messages` - Message records
- `conversations` - Chat conversations
- `conversation_participants` - User membership

---

### 9. **Notification System**
**Owner:** Kminh  
**Description:** Multi-channel notifications for events and updates

**Features:**
- In-app notifications
- Email notifications
- Real-time push notifications
- Notification preferences per user
- Notification scheduling
- Bulk notifications
- Event-triggered automation

**Notification Types:**
- Task assignments
- Project updates
- Leave approvals
- Productivity alerts
- System announcements
- Chatbot responses

**Delivery Channels:**
- Database (in-app)
- Email (SMTP)
- Webhook (JSON)
- Real-time (WebSocket)

---

### 10. **Online Documents Management**
**Owner:** Kminh  
**Description:** Centralized document storage and collaboration

**Features:**
- Document upload (PDF, DOCX, XLSX, TXT, etc.)
- Version control and history
- Document sharing with granular permissions
- Full-text search
- Document preview (with OnlyOffice integration)
- Collaborative editing capability
- Comments and annotations
- Access audit logs

**Storage:**
- AWS S3 for file storage
- Database metadata management

**Advanced Features:**
- Document templates
- Workflow attachments
- Document retention policies

---

### 11. **Reporting & Analytics**
**Owner:** Nguyen & Kminh  
**Description:** Data-driven insights and custom report generation

**Features:**

#### 11a. **Attendance Reports**
- Monthly/quarterly attendance summaries
- Tardiness analysis
- Absence trends
- Department comparisons

#### 11b. **Task Reports**
- Task completion metrics
- Task quality analysis
- Time estimation accuracy
- Work distribution analysis

#### 11c. **Productivity Reports**
- Daily productivity distribution
- Trending productivity metrics
- Productivity by department/project
- Predictive insights (via LSTM)

#### 11d. **Custom Reports**
- Report builder with filters
- Scheduled report generation
- Export to Excel/PDF
- Drill-down analytics

**Database Views:**
- Pre-aggregated fact tables for performance
- Dimensional analysis capabilities

---

### 12. **Calendar Management**
**Owner:** Tri  
**Description:** Integrated calendar and scheduling system

**Features:**
- Event scheduling
- Holiday calendar management
- Team availability view
- Recurring events
- Calendar sharing
- Sync with system check-ins
- Meeting room scheduling

**Database Tables:**
- `calendar_events` - Event records
- `holidays` - Holiday calendar entries

---

### 13. **Meeting Management**
**Owner:** Nguyen  
**Description:** Meeting scheduling with video conference integration

**Features:**
- Create and schedule meetings
- Participant invitation and RSVP
- Video conference integration
- Meeting notes recording
- Attendee tracking
- Meeting history and recordings
- Reminder notifications

**Integrations:**
- Google Meet API
- Zoom API (optional)

---

### 14. **Email Campaigns & Templates**
**Owner:** Nguyen  
**Description:** Marketing and communication campaign management

**Features:**
- Email template creation
- Shortcode support (dynamic placeholders)
- Campaign scheduling
- Recipient segmentation
- Template library
- Send history and analytics

**Database Tables:**
- `email_templates` - Template definitions
- `email_campaigns` - Campaign records
- `campaign_recipients` - Recipient tracking

**Template Features:**
- WYSIWYG editor
- Dynamic field insertion
- Batch send capability
- Bounce handling

---

### 15. **AI Chatbot Service**
**Owner:** Khoa  
**Description:** RAG-based conversational AI for document Q&A and productivity insights

#### Architecture:
```
User Query → FastAPI → Intent Detection → 
    ├─ General Q&A → Ollama Embedding → ChromaDB Query → Ollama Generation
    └─ Productivity → Flask ML API → Prediction + Response
```

#### Features:
- **Document Q&A**
  - Ingestion of multiple formats (PDF, DOCX, CSV, XLSX, TXT, PKL)
  - Vector embedding with Ollama
  - Semantic search in ChromaDB
  - Context-aware generation

- **Productivity Workflow**
  - Call Flask ML API for predictions
  - Query productivity vector database
  - Employee-specific productivity Q&A
  - Snapshot persistence for trending

- **Multi-Language Support**
  - Automatic language detection
  - Support for Vietnamese, English, and others
  - Language-specific embeddings

#### Endpoints:
- `POST /chat` - Chat with context
- `POST /refresh/productivity` - Rebuild productivity DB
- `GET /healthz` - Health check

#### Key Components:
- `Ollama` - LLM for embeddings and generation
- `FastAPI` - HTTP API
- `ChromaDB` - Vector database
- `Document Loaders` - Multi-format support

---

### 16. **LSTM Productivity Forecasting System**
**Owner:** Nguyen  
**Description:** Deep learning model predicting next-day productivity class

#### What It Does:
Predicts each employee's productivity class (Low/Medium/High) for tomorrow based on past 14 days of behavioral data.

#### System Flow:
```
MySQL (Daily Data) → ETL Pipeline → PostgreSQL (DW) → 
LSTM Training → Flask API → Laravel Dashboard
```

#### Architecture:
```
Input (14 days × 39 features)
    ↓
LSTM Layer 1 (64 units, return_sequences=True)
    ↓
Dropout(0.3)
    ↓
LSTM Layer 2 (32 units)
    ↓
Dropout(0.3)
    ↓
Dense(16, ReLU)
    ↓
Dense(3, Softmax) → Output (Low/Medium/High)
```

#### Features (39 Total):

**Original 27 Features:**
- User Context (2): `employee_id`, `department_id`
- Attendance (6): `checked_in`, `is_late`, `had_day_off`, `is_half_day_off`, `leave_type`, `is_holiday`
- Tasks (5): `tasks_completed`, `tasks_in_progress`, `avg_task_score`, `avg_task_percentage`, `active_task_count`
- Attendance Rates (6): `attendance_rate_7d`, `attendance_rate_30d`, `late_rate_7d`, `late_rate_30d`, `dayoff_rate_7d`, `dayoff_rate_30d`
- Task Signals (2): `task_score_7d_avg`, `task_completed_rate_7d`
- Streaks (2): `checkin_streak`, `task_completion_streak`
- Historical Scores (8): `productivity_score_1d_ago` through `productivity_score_7d_ago`

**New ETL v2 Features (12):**
- Timing Signals (3): `checkin_hour`, `minutes_late`, `time_at_office_h`
- Task Pressure (5): `high_priority_task_count`, `days_to_nearest_deadline`, `overdue_task_count`, `total_estimated_hours`, `task_pressure_index`
- Calendar Context (4): `is_half_day_off`, `is_holiday`, `is_post_holiday`, `days_since_holiday`

#### Training:
- **Algorithm**: LSTM with categorical classification
- **Lookback**: 14 days
- **Output**: 3 classes (Low/Medium/High productivity)
- **Training Epochs**: 120 with early stopping (patience=10)
- **Accuracy**: 70-72% (with enriched features)
- **Baseline Comparison**: Deterministic productivity formula

#### Deployment:
```
Flask API (port 5001)
├─ GET /predict/{user_id} → Single prediction
├─ POST /predict/all → Batch prediction for all employees
└─ GET /health → Health check
```

#### Laravel Integration:
```php
GET /api/lstm/stats                    // Overall statistics
GET /api/lstm/employee-predictions     // All predictions
GET /api/lstm/employee-history/{id}    // Historical trends
POST /api/lstm/refresh-predictions     // Trigger batch prediction
GET /api/lstm/export-excel             // Export to spreadsheet
```

#### Dashboard Display (4-Tier):
1. **Snapshot**: Current aggregate predictions (% Low/Med/High)
2. **Who Needs Attention**: List of Low productivity employees
3. **Context**: Employee details, historical trends, explanations
4. **About**: Model documentation and limitations

#### ETL Pipeline:
Data flow from MySQL operational database to PostgreSQL data warehouse:

**Dimension Tables:**
- `dim_date` (Full calendar 2018-2030)
- `dim_employee` (SCD - Slowly Changing Dimension)
- `dim_department`
- `dim_project`
- `dim_phase`
- `dim_task`

**Fact Table:**
- `fact_employee_productivity` (One row per employee-date)

**Productivity Score Formula:**
```
If had_day_off AND not checked_in: score = 0.0

Has Task Signal Branch:
  productivity = (
    0.25 * attendance +
    0.25 * hours_score +
    0.30 * task_completion_pct +
    0.20 * task_quality_score
  ) * 100

No Task Signal Branch:
  productivity = (
    0.60 * attendance +
    0.40 * hours_score
  ) * 100

Where:
  hours_score = min(hours_worked / 8, 1)
  attendance = 1.0 (on-time) | 0.5 (late) | 0.0 (absent)
  task_completion_pct = min(avg_percentage / 100, 1)
  task_quality_score = min(avg_quality / 10, 1)
```

---

### 17. **Face Detection Module**
**Owner:** Tri  
**Description:** Facial recognition for attendance verification

**Features:**
- Real-time face detection via webcam
- Face recognition against user database
- Liveness detection (prevents photo spoofing)
- Integration with check-in system
- Accuracy metrics

**Technology:**
- `face-api.js` - Browser-based face detection
- Neural network models pre-trained

---

### 18. **Whiteboard Collaboration**
**Owner:** Nguyen  
**Description:** Real-time collaborative drawing and diagramming

**Features:**
- Real-time collaborative drawing
- Shape and text tools
- Color palette and brush styles
- Undo/redo functionality
- Export to image/PDF
- Shared workspaces
- User cursor tracking

**Technology:**
- WhiteBophir integration
- Canvas-based rendering
- WebSocket for real-time sync

---

### 19. **Speech-to-Text & Voice Commands**
**Owner:** Tri  
**Description:** Audio input processing for hands-free interaction

**Features:**
- Real-time speech recognition
- Voice command parsing
- Multi-language support
- Command execution
- Transcription display
- Confidence scoring

**API Integration:**
- Web Speech API
- Google Speech-to-Text (optional)

---

## How It Works

### User Journey: Task Assignment to Completion

```
1. HR/Manager Creates Project
   └─ Specifies project phases, dates, assigned staff

2. Manager Creates Tasks within Project
   └─ Sets title, description, assignees, dates, priority
   └─ Task quality scoring framework established

3. Employee Receives Task Notification
   └─ Via in-app notification, email, and chat

4. Employee Works on Task
   └─ System tracks daily check-ins, working hours
   └─ Employee logs progress percentage
   └─ Subtasks progressed

5. ETL Pipeline Runs (Nightly)
   └─ Aggregates daily attendance and task data
   └─ Calculates daily productivity score
   └─ Stores in data warehouse (PostgreSQL)

6. LSTM Training (Scheduled)
   └─ Processes 14 days of historical data per employee
   └─ Generates tomorrow's productivity prediction
   └─ Stores predictions in Flask API cache

7. Dashboard Display
   └─ Manager views next-day predictions
   └─ Identifies at-risk employees
   └─ Takes proactive interventions

8. Task Completion
   └─ Employee marks task complete
   └─ Submits quality score for evaluation
   └─ System records completion date/time
```

### Real-Time Data Flow (Chat & Notifications)

```
User Action (Message Sent)
    ↓
Laravel Controller
    ↓
Database Persist
    ↓
Event Broadcast (JSON)
    ↓
Pusher/Redis Queue
    ↓
WebSocket Delivery
    ↓
Real-time UI Update (JavaScript)
```

### AI Chatbot Query Flow

```
User: "What was John's productivity last week?"
    ↓
FastAPI Receives Query
    ↓
Intent Detection (Productivity Query)
    ↓
Call Flask /predict/{john_id}
    ↓
Retrieve Historical Predictions
    ↓
Call Ollama for Natural Language Response
    ↓
Return Formatted Answer + Context
    ↓
Display in Chat UI
```

---

## Team Responsibilities

### Khoa - Core HR Platform
**Components:**
- Workspace management and organization
- Project CRUD operations with phase tracking
- Task creation, assignment, and status management
- Attendance and check-in infrastructure
- User & role management framework
- Chatbot service integration

**Key Files:**
- `app/Http/Controllers/ProjectController.php`
- `app/Http/Controllers/TaskController.php`
- `app/Services/ProjectService.php`
- `app/Services/TaskService.php`
- `app/Models/Project.php`, `Task.php`, `User.php`

---

### Nguyen - ML & Predictive Analytics
**Components:**
- LSTM model for next-day productivity prediction
- User profile management and implementation
- Department permission configuration
- Meeting/calendar management
- Email campaigns and template systems
- Working hours configuration
- Whiteboard collaboration
- Reports generation
- Profile management and settings

**Key Files:**
- `ml/train_lstm_nextday.py` - LSTM training
- `ml/api.py` - Flask inference API
- `ml/evaluate_classifier_nextday.py` - Model evaluation
- `etl/etl_pipeline.py` - Data warehouse ETL
- `app/Services/ProductivityCalculatorService.php`
- `app/Http/Controllers/LSTMDashboardController.php`

---

### Kminh - Search, Collaboration & Analytics
**Components:**
- Advanced full-text search with filtering/sorting
- Department management and hierarchy
- Real-time chat system
- Notification system (multi-channel)
- Online document management and collaboration
- Summary agents (AI-powered analysis)
- Reports and data analysis
- Search indexing

**Key Files:**
- `app/Http/Controllers/ChatController.php`
- `app/Http/Controllers/NotificationController.php`
- `app/Models/Message.php`, `Conversation.php`
- `app/Http/Controllers/DocumentController.php`

---

### Tri - UI/UX & User-Facing Features
**Components:**
- Speech-to-text and voice commands
- Calendar management and synchronization
- User interface design and styling
- Day-off request system with approval workflow
- Holiday management (half-day handling)
- Face detection for attendance verification
- Poster and announcement design

**Key Files:**
- `resources/views/` - All Blade templates
- `app/Http/Controllers/DayOffRequestController.php`
- `face_detection/app.py`
- Frontend JavaScript/CSS for calendar and voice

---

## Results & Outputs

### 1. **Dashboard Outputs**

#### Productivity Prediction Dashboard
- **Display**: Real-time predictions for all employees
- **Metrics**: % Low / % Medium / % High productivity
- **Drill-Down**: Individual employee trends and context
- **Export**: Excel reports for further analysis

#### Task Completion Metrics
- **Burndown Charts**: Project/sprint progress
- **Velocity Metrics**: Team throughput analysis
- **Time Estimation Accuracy**: Forecast vs. actual

#### Attendance Analytics
- **Monthly Summaries**: Presence percentage by department
- **Tardiness Trends**: Late arrival analysis
- **Absence Patterns**: Absence reasons and frequency

### 2. **Model Outputs**

#### LSTM Predictions (Per Employee, Daily)
```json
{
  "employee_id": 123,
  "prediction_date": "2025-05-10",
  "predicted_class": "High",
  "confidence_scores": {
    "Low": 0.15,
    "Medium": 0.25,
    "High": 0.60
  },
  "explanation": "Based on consistent task completion and on-time arrivals",
  "recommendation": "Monitor for workload balance"
}
```

#### Batch Prediction Results
- JSON format for all employees
- Cached in Flask API memory
- Refreshed on nightly trigger
- Queryable by date range

### 3. **Report Outputs**

#### Excel Exports
- Multi-sheet workbooks
- Formatted with charts
- Filterable tables
- Timestamp and version info

#### PDF Reports
- Formatted pages
- Charts and graphics
- Executive summaries
- Detailed appendices

### 4. **Real-Time Outputs**

#### Chat Messages
- Persistent in database
- Real-time delivery via WebSocket
- Full-text searchable
- With attachments and reactions

#### Notifications
- In-app notification badges
- Email notifications
- Push notifications (via Pusher)
- Webhook delivery

### 5. **Data Warehouse Outputs**

#### OLAP Cube
- Aggregated fact tables
- Pre-computed dimensional queries
- Ready for BI tool integration
- Optimized for reporting

#### Event Log
- Complete audit trail
- All user actions recorded
- Timestamps and user context
- Searchable by date/user

---

## Extensions & Enhancements

### Current/Planned Extensions

#### 1. **LSTM v2 Enhancement** ✅ Complete
- **Status**: Implemented and fully documented
- **Change**: Feature expansion from 27 → 39 features
- **New Features**:
  - Timing signals (check-in hour, minutes late, office duration)
  - Task pressure metrics (priority count, deadline proximity, overdue tasks)
  - Calendar context (holiday proximity, half-day flags)
- **Expected Improvement**: +3-4% accuracy (66-68% → 70-72%)
- **Files Modified**:
  - `ml/train_lstm_nextday.py`
  - `ml/evaluate_classifier_nextday.py`
  - `ml/api.py`
  - `etl/etl_pipeline_v2.py` (adds 12 new columns)

#### 2. **ARIMA-Derived Features** ⏳ Planned
- Use ARIMA models for continuous probability features
- Replace binary attendance flags with trend probabilities
- Expected to improve handling of patterns

#### 3. **Advanced Search**
- Full-text search across all entities
- Elasticsearch integration (optional)
- Advanced filtering and faceting

#### 4. **Mobile App**
- React Native/Flutter mobile client
- Offline sync capability
- Native camera access for face detection

#### 5. **API Analytics**
- Request/response logging
- Endpoint performance metrics
- Rate limiting per user/role

#### 6. **Workflow Automation**
- Custom workflow builders
- Conditional logic and branching
- Scheduled task triggers

#### 7. **Advance Analytics**
- Cohort analysis
- Retention metrics
- Pipeline analysis
- Forecasting

---

## Project Setup & Deployment

### Prerequisites
- PHP 8.2+
- Node.js 16+
- Python 3.9+ (for ML)
- MySQL 5.7+ / PostgreSQL 12+
- Composer
- npm/yarn

### Installation Steps

#### 1. Clone Repository
```bash
git clone https://github.com/HillaryNguyen2004/DO_AN_CHUYEN_NGANH.git
cd DO_AN_CHUYEN_NGANH
```

#### 2. Install PHP Dependencies
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

#### 3. Install JavaScript Dependencies
```bash
npm install
npm run dev
```

#### 4. Setup Databases
```bash
# Create MySQL database (operational)
php artisan migrate
php artisan db:seed

# Create PostgreSQL database (data warehouse)
# Run etl/etl_pipeline.py to initialize DW schema
```

#### 5. Configure Environment
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=manage_user
DB_USERNAME=root

DW_HOST=127.0.0.1
DW_DATABASE=dw_productivity
DW_USERNAME=postgres

PUSHER_APP_ID=xxx
PUSHER_APP_KEY=xxx

ML_API_URL=http://localhost:5001
```

#### 6. Start Services

**Laravel Server:**
```bash
php artisan serve
```

**ML Service (Flask):**
```bash
cd ml/
python api.py
```

**Chatbot Service (FastAPI):**
```bash
cd chatbot_service/
python api/app.py
```

**Frontend Build:**
```bash
npm run dev    # Development
npm run build  # Production
```

#### 7. Run ETL Pipeline
```bash
cd etl/
python etl_pipeline.py
```

#### 8. Train LSTM Model
```bash
cd ml/
python train_lstm_nextday.py
```

### Docker Deployment
```bash
docker-compose up -d
```

### Production Deployment (Vercel)
- Configure `vercel.json` for serverless deployment
- Set environment variables in Vercel dashboard
- Deploy API and frontend separately

---

## Summary

**DO_AN_CHUYEN_NGANH** is a sophisticated, production-grade HR management system that combines:

✅ **Complete HR Operations** - Projects, tasks, attendance, leave management  
✅ **AI-Powered Insights** - LSTM next-day productivity forecasting  
✅ **Intelligent Automation** - RAG chatbot with document understanding  
✅ **Real-Time Collaboration** - Chat, notifications, document management  
✅ **Data-Driven Decisions** - ETL, data warehouse, comprehensive reporting  
✅ **Modern Architecture** - Microservices, scalable, production-ready  

The system demonstrates advanced software engineering practices including service-oriented architecture, machine learning integration, real-time communication, and scalable data processing—making it a comprehensive solution for modern enterprise HR needs.

---

**Last Updated:** May 10, 2026  
**Version:** 2.0  
**Status:** Active Development  
**Repository:** https://github.com/HillaryNguyen2004/DO_AN_CHUYEN_NGANH
