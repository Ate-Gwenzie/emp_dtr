-- 1. Create the USERS table (Parent table for login credentials)
CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  email varchar(255) NOT NULL,
  password varchar(255) NOT NULL,
  user_type varchar(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create the ADMIN table (The one causing your current error)
CREATE TABLE admin (
  adid int(11) NOT NULL AUTO_INCREMENT,
  fname_ad varchar(100) NOT NULL,
  lname_ad varchar(100) NOT NULL,
  email_ad varchar(255) NOT NULL,
  pass_ad varchar(255) NOT NULL,
  user_id int(11) NOT NULL,
  PRIMARY KEY (adid),
  KEY user_id (user_id),
  CONSTRAINT fk_admin_users FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create the EMPLOYEE table
CREATE TABLE employee (
  empid int(11) NOT NULL AUTO_INCREMENT,
  employee_id varchar(50) NOT NULL,
  fname_emp varchar(100) NOT NULL,
  lname_emp varchar(100) NOT NULL,
  email_emp varchar(255) NOT NULL,
  position varchar(100) NOT NULL,
  pass_emp varchar(255) DEFAULT NULL,
  timein_am time DEFAULT NULL,
  timeout_am time DEFAULT NULL,
  timein_pm time DEFAULT NULL,
  timeout_pm time DEFAULT NULL,
  user_id int(11) NOT NULL,
  verification_token varchar(255) DEFAULT NULL,
  is_verified tinyint(1) DEFAULT 0,
  PRIMARY KEY (empid),
  KEY user_id (user_id),
  CONSTRAINT fk_employee_users FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create the ATTENDANCE table
CREATE TABLE attendance (
  id int(11) NOT NULL AUTO_INCREMENT,
  employee_id int(11) NOT NULL,
  date date NOT NULL,
  time_in_am time DEFAULT NULL,
  time_out_am time DEFAULT NULL,
  time_in_pm time DEFAULT NULL,
  time_out_pm time DEFAULT NULL,
  status varchar(50) DEFAULT NULL,
  confirmation_status varchar(50) DEFAULT 'Draft',
  PRIMARY KEY (id),
  KEY employee_id (employee_id),
  CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employee (empid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create LEAVE_APPLICATIONS table
CREATE TABLE leave_applications (
  leave_id int(11) NOT NULL AUTO_INCREMENT,
  employee_id int(11) NOT NULL,
  start_date date NOT NULL,
  end_date date NOT NULL,
  reason text NOT NULL,
  status varchar(50) DEFAULT 'Pending',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (leave_id),
  KEY employee_id (employee_id),
  CONSTRAINT fk_leave_employee FOREIGN KEY (employee_id) REFERENCES employee (empid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create EARLY_TIMEOUT_REQUESTS table
CREATE TABLE early_timeout_requests (
  request_id int(11) NOT NULL AUTO_INCREMENT,
  employee_id int(11) NOT NULL,
  request_date date NOT NULL,
  session_type varchar(10) NOT NULL,
  reason text NOT NULL,
  requested_time time NOT NULL,
  status varchar(50) DEFAULT 'Pending',
  admin_action_date datetime DEFAULT NULL,
  PRIMARY KEY (request_id),
  KEY employee_id (employee_id),
  CONSTRAINT fk_early_req_employee FOREIGN KEY (employee_id) REFERENCES employee (empid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_reset_requests (
  request_id int(11) NOT NULL AUTO_INCREMENT,
  employee_id int(11) NOT NULL,
  employee_email varchar(255) NOT NULL,
  reason text NOT NULL,
  new_pass_hash varchar(255) NOT NULL,
  status varchar(50) DEFAULT 'Pending',
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  admin_action_date datetime DEFAULT NULL,
  PRIMARY KEY (request_id),
  KEY employee_id (employee_id),
  CONSTRAINT fk_pass_req_employee FOREIGN KEY (employee_id) REFERENCES employee (empid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Create NOTIFICATIONS table
CREATE TABLE notifications (
  id int(11) NOT NULL AUTO_INCREMENT,
  recipient_type varchar(50) NOT NULL, -- 'admin' or 'employee'
  recipient_id int(11) NOT NULL,
  notification_type varchar(100) NOT NULL,
  message text NOT NULL,
  is_read tinyint(1) DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
