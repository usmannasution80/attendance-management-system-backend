# Attendance Management System (Back-End)
## Overview
This is a web based attendance management system application. It's for **back-end**. This application require a **front-end** which you can find in [Attendance Management System (Front-End)](https://github.com/usmannasution80/attendance-management-system-frontend). This application is powered by [Laravel](https://laravel.com/).
## Installation
### Requirements
- PHP >= 8.1
- MySQL
- NodeJS
- Composer (PHP)
- [Attendance Management System (Front-End)](https://github.com/usmannasution80/attendance-management-system-backend).
### Development
If you want to run this application in development mode, you should  you should run the **back-end** at **port 8000** (it's default) and this **front-end** at **port 3000** (it's default).
Navigate to **front-end** directory, and run this command :
```
npm install
npm start &
```
Now, navigate to **back-end** directory and run this command :
```
php artisan migrate
php artisan serve &
```
Make sure MySQL server already running at **port 3306**.
Default admin for this application is :
- Email : **ams_admin@gmail.com**
- Password : **ams_admin**
### Production
If you want to run in the production mode, you can follow these steps :
- Firstly, follow [these steps](https://github.com/usmannasution80/attendance-management-system-frontend#production).
- Navigate to **back-end** directory.
- Make sure MySQL server already running at port **3306**.
- Run these command :
  ```
  php artisan migrate
  php artisan serve
  ```
- You can access ```http://127.0.0.1:8000```
- If you want to use a web server like **NGINX**, make sure the root is pointed to ```path/to/backend/public```.
- Default admin for this application is :
  - Email : **ams_admin@gmail.com**
  - Password : **ams_admin**