CREATE TABLE IF NOT EXISTS user_ (
	userID INT PRIMARY KEY AUTO_INCREMENT,
	firstname varchar(255),
	lastname varchar(255),
	dob DATE,
	username varchar(255),
	pwd varchar(255),
	role enum('customer', 'pharmacist', 'admin')
);
CREATE TABLE IF NOT EXISTS customer (
	customerID INT PRIMARY KEY AUTO_INCREMENT,
	firstname varchar(255),
	lastname varchar(255),
	gender enum('man', 'woman'),
	dob DATE,
	postcode BIGINT
);
CREATE TABLE IF NOT EXISTS drug (
	drugID INT PRIMARY KEY AUTO_INCREMENT,
	name varchar(255),
	basic_unit INT,
	collective_unit INT,
	no_of_basic_units_in_collective_unit decimal,
	age_limit int
	 
);
CREATE TABLE IF NOT EXISTS stock (
	stockID INT  PRIMARY KEY AUTO_INCREMENT,
	drugID INT,
	name varchar(255),
	quantity INT,
	batch_number BIGINT,
	buying_price_per_pack decimal,
	selling_price_per_pack decimal,
	expiry_date DATE,
	 FOREIGN KEY (drugID) REFERENCES drug(drugID)
);
CREATE TABLE IF NOT EXISTS allergies (
	allergyID INT  PRIMARY KEY AUTO_INCREMENT,
	drugID INT,
	description TEXT,
	customerID INT,
	 FOREIGN KEY (drugID) REFERENCES drug(drugID),
	 FOREIGN KEY (customerID) REFERENCES customer(customerID)
);
CREATE TABLE IF NOT EXISTS med_condition_history (
	historyID INT  PRIMARY KEY AUTO_INCREMENT,
	description TEXT,
	customerID INT,
	 FOREIGN KEY (customerID) REFERENCES customer(customerID)
);
CREATE TABLE IF NOT EXISTS order_ (
	orderID INT  PRIMARY KEY AUTO_INCREMENT,
	status enum('pending', 'paid', 'cancelled'),
	created_at DATETIME,
	customerID INT,
	userID INT,
	 FOREIGN KEY (customerID) REFERENCES customer(customerID),
	 FOREIGN KEY (userID) REFERENCES user_(userID)
);
CREATE TABLE IF NOT EXISTS order_item (
	order_itemID INT  PRIMARY KEY AUTO_INCREMENT,
	orderID INT,
	drugID INT,
	price decimal,
	 FOREIGN KEY (orderID) REFERENCES order_(orderID),
	 FOREIGN KEY (drugID) REFERENCES drug(drugID)
);
CREATE TABLE IF NOT EXISTS invoice (
	invoiceID INT  PRIMARY KEY AUTO_INCREMENT,
	orderID INT,
	total_amount decimal,
	date_invoice DATE,
	status enum('pending', 'paid', 'cancelled'),
	 FOREIGN KEY (orderID) REFERENCES order_(orderID)
);