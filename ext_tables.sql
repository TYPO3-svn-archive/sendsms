#
# Table structure for table "tx_sendsms_sms"
#
CREATE TABLE tx_sendsms_sms (
  fe_user_id int(11) unsigned NOT NULL,
  sms_sent int(11) unsigned DEFAULT '0' NOT NULL,
  sms_sent_in_period int(11) unsigned DEFAULT '0' NOT NULL,
  period_start datetime NOT NULL,
  period_end datetime NOT NULL,
  PRIMARY KEY (fe_user_id)
);
#
# Table structure for table "tx_sendsms_statistics"
#
CREATE TABLE tx_sendsms_statistics (
  sms_id INT NOT NULL AUTO_INCREMENT,
  sms_day int(11) unsigned NOT NULL,
  sms_month int(11) unsigned NOT NULL,
  sms_year int(11) unsigned NOT NULL,
  sms_hour int(11) unsigned NOT NULL,
  sms_length int(11) unsigned NOT NULL,
  PRIMARY KEY (sms_id)
);