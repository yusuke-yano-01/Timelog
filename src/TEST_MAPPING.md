# テスト内容とテスト関数の対応表

このドキュメントは、テスト内容と実際のテスト関数名の対応関係を示します。

## 認証機能（一般ユーザー）

| テスト内容                                                          | テスト関数名                                                         | ファイル                         |
| ------------------------------------------------------------------- | -------------------------------------------------------------------- | -------------------------------- |
| 名前が未入力の場合、バリデーションメッセージが表示される            | `test_name_validation_when_name_is_empty()`                          | `RegistrationValidationTest.php` |
| メールアドレスが未入力の場合、バリデーションメッセージが表示される  | `test_email_validation_when_email_is_empty()`                        | `RegistrationValidationTest.php` |
| パスワードが 8 文字未満の場合、バリデーションメッセージが表示される | `test_password_validation_when_password_is_less_than_8_characters()` | `RegistrationValidationTest.php` |
| パスワードが一致しない場合、バリデーションメッセージが表示される    | `test_password_validation_when_passwords_do_not_match()`             | `RegistrationValidationTest.php` |
| パスワードが未入力の場合、バリデーションメッセージが表示される      | `test_password_validation_when_password_is_empty()`                  | `RegistrationValidationTest.php` |
| フォームに内容が入力されていた場合、データが正常に保存される        | `test_user_registration_saves_data_when_form_is_valid()`             | `RegistrationValidationTest.php` |

## ログイン認証機能（一般ユーザー）

| テスト内容                                                         | テスト関数名                                                 | ファイル                  |
| ------------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------- |
| メールアドレスが未入力の場合、バリデーションメッセージが表示される | `test_user_login_validation_when_email_is_empty()`           | `LoginValidationTest.php` |
| パスワードが未入力の場合、バリデーションメッセージが表示される     | `test_user_login_validation_when_password_is_empty()`        | `LoginValidationTest.php` |
| 登録内容と一致しない場合、バリデーションメッセージが表示される     | `test_user_login_validation_when_credentials_do_not_match()` | `LoginValidationTest.php` |

## ログイン認証機能（管理者）

| テスト内容                                                         | テスト関数名                                                  | ファイル                  |
| ------------------------------------------------------------------ | ------------------------------------------------------------- | ------------------------- |
| メールアドレスが未入力の場合、バリデーションメッセージが表示される | `test_admin_login_validation_when_email_is_empty()`           | `LoginValidationTest.php` |
| パスワードが未入力の場合、バリデーションメッセージが表示される     | `test_admin_login_validation_when_password_is_empty()`        | `LoginValidationTest.php` |
| 登録内容と一致しない場合、バリデーションメッセージが表示される     | `test_admin_login_validation_when_credentials_do_not_match()` | `LoginValidationTest.php` |

## 日時取得機能

| テスト内容                                     | テスト関数名                                     | ファイル             |
| ---------------------------------------------- | ------------------------------------------------ | -------------------- |
| 現在の日時情報が UI と同じ形式で出力されている | `test_current_datetime_is_displayed_correctly()` | `AttendanceTest.php` |

## ステータス確認機能

| テスト内容                                     | テスト関数名                                      | ファイル             |
| ---------------------------------------------- | ------------------------------------------------- | -------------------- |
| 勤務外の場合、勤怠ステータスが正しく表示される | `test_status_display_when_user_is_off_duty()`     | `AttendanceTest.php` |
| 出勤中の場合、勤怠ステータスが正しく表示される | `test_status_display_when_user_is_on_duty()`      | `AttendanceTest.php` |
| 休憩中の場合、勤怠ステータスが正しく表示される | `test_status_display_when_user_is_on_break()`     | `AttendanceTest.php` |
| 退勤済の場合、勤怠ステータスが正しく表示される | `test_status_display_when_user_has_clocked_out()` | `AttendanceTest.php` |

## 出勤機能

| テスト内容                         | テスト関数名                                       | ファイル             |
| ---------------------------------- | -------------------------------------------------- | -------------------- |
| 出勤ボタンが正しく機能する         | `test_clock_in_functionality()`                    | `AttendanceTest.php` |
| 出勤は一日一回のみできる           | `test_clock_in_only_once_per_day()`                | `AttendanceTest.php` |
| 出勤時刻が勤怠一覧画面で確認できる | `test_clock_in_time_is_recorded_in_timelog_list()` | `AttendanceTest.php` |

## 休憩機能

| テスト内容                         | テスト関数名                                    | ファイル             |
| ---------------------------------- | ----------------------------------------------- | -------------------- |
| 休憩ボタンが正しく機能する         | `test_start_break_functionality()`              | `AttendanceTest.php` |
| 休憩は一日に何回でもできる         | `test_break_can_be_taken_multiple_times()`      | `AttendanceTest.php` |
| 休憩戻ボタンが正しく機能する       | `test_end_break_functionality()`                | `AttendanceTest.php` |
| 休憩戻は一日に何回でもできる       | `test_end_break_can_be_done_multiple_times()`   | `AttendanceTest.php` |
| 休憩時刻が勤怠一覧画面で確認できる | `test_break_time_is_recorded_in_timelog_list()` | `AttendanceTest.php` |

## 退勤機能

| テスト内容                         | テスト関数名                                        | ファイル             |
| ---------------------------------- | --------------------------------------------------- | -------------------- |
| 退勤ボタンが正しく機能する         | `test_clock_out_functionality()`                    | `AttendanceTest.php` |
| 退勤時刻が勤怠一覧画面で確認できる | `test_clock_out_time_is_recorded_in_timelog_list()` | `AttendanceTest.php` |

## 勤怠一覧情報取得機能（一般ユーザー）

| テスト内容                                           | テスト関数名                                                      | ファイル          |
| ---------------------------------------------------- | ----------------------------------------------------------------- | ----------------- |
| 自分が行った勤怠情報が全て表示されている             | `test_user_timelog_list_displays_all_attendance_records()`        | `TimelogTest.php` |
| 勤怠一覧画面に遷移した際に現在の月が表示される       | `test_user_timelog_list_displays_current_month()`                 | `TimelogTest.php` |
| 「前月」を押下した時に表示月の前月の情報が表示される | `test_user_timelog_list_displays_previous_month()`                | `TimelogTest.php` |
| 「翌月」を押下した時に表示月の前月の情報が表示される | `test_user_timelog_list_displays_next_month()`                    | `TimelogTest.php` |
| 「詳細」を押下すると、その日の勤怠詳細画面に遷移する | `test_user_timelog_list_detail_button_redirects_to_detail_page()` | `TimelogTest.php` |

## 勤怠詳細情報取得機能（一般ユーザー）

| テスト内容                                                               | テスト関数名                                                   | ファイル          |
| ------------------------------------------------------------------------ | -------------------------------------------------------------- | ----------------- |
| 勤怠詳細画面の「名前」がログインユーザーの氏名になっている               | `test_user_timelog_detail_displays_user_name()`                | `TimelogTest.php` |
| 勤怠詳細画面の「日付」が選択した日付になっている                         | `test_user_timelog_detail_displays_selected_date()`            | `TimelogTest.php` |
| 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している | `test_user_timelog_detail_displays_correct_attendance_times()` | `TimelogTest.php` |
| 「休憩」にて記されている時間がログインユーザーの打刻と一致している       | `test_user_timelog_detail_displays_correct_break_times()`      | `TimelogTest.php` |

## 勤怠詳細情報修正機能（一般ユーザー）

| テスト内容                                                                 | テスト関数名                                                                       | ファイル                |
| -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ----------------------- |
| 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される     | `test_user_timelog_update_validation_when_arrival_time_after_departure_time()`     | `TimelogUpdateTest.php` |
| 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される | `test_user_timelog_update_validation_when_break_start_time_after_departure_time()` | `TimelogUpdateTest.php` |
| 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される | `test_user_timelog_update_validation_when_break_end_time_after_departure_time()`   | `TimelogUpdateTest.php` |
| 備考欄が未入力の場合のエラーメッセージが表示される                         | `test_user_timelog_update_validation_when_note_is_empty()`                         | `TimelogUpdateTest.php` |
| 修正申請処理が実行される                                                   | `test_user_timelog_update_creates_application()`                                   | `TimelogUpdateTest.php` |
| 「承認待ち」にログインユーザーが行った申請が全て表示されていること         | `test_user_application_list_displays_pending_applications()`                       | `TimelogUpdateTest.php` |
| 「承認済み」に管理者が承認した修正申請が全て表示されている                 | `test_user_application_list_displays_approved_applications()`                      | `TimelogUpdateTest.php` |
| 各申請の「詳細」を押下すると勤怠詳細画面に遷移する                         | `test_user_application_detail_button_redirects_to_detail_page()`                   | `TimelogUpdateTest.php` |

## 勤怠一覧情報取得機能（管理者）

| テスト内容                                             | テスト関数名                                                 | ファイル               |
| ------------------------------------------------------ | ------------------------------------------------------------ | ---------------------- |
| その日になされた全ユーザーの勤怠情報が正確に確認できる | `test_admin_attendance_list_displays_all_users_attendance()` | `AdminTimelogTest.php` |
| 遷移した際に現在の日付が表示される                     | `test_admin_attendance_list_displays_current_date()`         | `AdminTimelogTest.php` |
| 「前日」を押下した時に前の日の勤怠情報が表示される     | `test_admin_attendance_list_displays_previous_date()`        | `AdminTimelogTest.php` |
| 「翌日」を押下した時に次の日の勤怠情報が表示される     | `test_admin_attendance_list_displays_next_date()`            | `AdminTimelogTest.php` |

## 勤怠詳細情報取得・修正機能（管理者）

| テスト内容                                                                 | テスト関数名                                                                        | ファイル               |
| -------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- | ---------------------- |
| 勤怠詳細画面に表示されるデータが選択したものになっている                   | `test_admin_attendance_detail_displays_selected_data()`                             | `AdminTimelogTest.php` |
| 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される     | `test_admin_timelog_update_validation_when_arrival_time_after_departure_time()`     | `AdminTimelogTest.php` |
| 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される | `test_admin_timelog_update_validation_when_break_start_time_after_departure_time()` | `AdminTimelogTest.php` |
| 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される | `test_admin_timelog_update_validation_when_break_end_time_after_departure_time()`   | `AdminTimelogTest.php` |
| 備考欄が未入力の場合のエラーメッセージが表示される                         | `test_admin_timelog_update_validation_when_note_is_empty()`                         | `AdminTimelogTest.php` |

## ユーザー情報取得機能（管理者）

| テスト内容                                                             | テスト関数名                                                        | ファイル               |
| ---------------------------------------------------------------------- | ------------------------------------------------------------------- | ---------------------- |
| 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる | `test_admin_staff_list_displays_all_staff_info()`                   | `AdminTimelogTest.php` |
| ユーザーの勤怠情報が正しく表示される                                   | `test_admin_staff_timelog_displays_correctly()`                     | `AdminTimelogTest.php` |
| 「前月」を押下した時に表示月の前月の情報が表示される                   | `test_admin_staff_timelog_displays_previous_month()`                | `AdminTimelogTest.php` |
| 「翌月」を押下した時に表示月の前月の情報が表示される                   | `test_admin_staff_timelog_displays_next_month()`                    | `AdminTimelogTest.php` |
| 「詳細」を押下すると、その日の勤怠詳細画面に遷移する                   | `test_admin_staff_timelog_detail_button_redirects_to_detail_page()` | `AdminTimelogTest.php` |

## 勤怠情報修正機能（管理者）

| テスト内容                               | テスト関数名                                                   | ファイル               |
| ---------------------------------------- | -------------------------------------------------------------- | ---------------------- |
| 承認待ちの修正申請が全て表示されている   | `test_admin_application_list_displays_pending_applications()`  | `AdminTimelogTest.php` |
| 承認済みの修正申請が全て表示されている   | `test_admin_application_list_displays_approved_applications()` | `AdminTimelogTest.php` |
| 修正申請の詳細内容が正しく表示されている | `test_admin_application_detail_displays_correct_content()`     | `AdminTimelogTest.php` |
| 修正申請の承認処理が正しく行われる       | `test_admin_application_approval_updates_attendance()`         | `AdminTimelogTest.php` |

## 注意事項

-   すべてのテストが実装済みです。
-   テスト関数名は、`--filter`オプションを使用して個別に実行できます。
    -   例: `docker-compose exec php php artisan test --filter test_name_validation_when_name_is_empty`
