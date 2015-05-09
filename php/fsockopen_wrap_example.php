<?php

	define('CLIENT_CONNECT_TIMEOUT', 10);
	define('CLIENT_CMD_TIMEOUT', 5);
	define('CLIENT_READ_CHUNKS', 512);

	/**
	 * fsockopen wrapper example
	 */
	class Client {

		private $sock;

		public $ip;
		public $port;

		public function __construct($ip, $port) {
			$this->ip = $ip;
			$this->port = $port;
		}

		/**
		 * internal sock methods
		 */
		private function read() {
			$res = ''; $unread_bytes = null;
			$res = fread($this->sock, CLIENT_READ_CHUNKS);
			$unread_bytes = stream_get_meta_data($this->sock)['unread_bytes'];
			while ($unread_bytes > 0) {
				if ($unread_bytes > CLIENT_READ_CHUNKS) {
					$res .= fread($this->sock, CLIENT_READ_CHUNKS);
					$unread_bytes -= CLIENT_READ_CHUNKS;
				}
				else {
					$res .= fread($this->sock, $unread_bytes);
					$unread_bytes = 0;
				}
			}
			return $res;
		}

		private function write($data) {
			fwrite($this->sock, $data);
		}

		private function is_timed_out() {
			if (stream_get_meta_data($this->sock)['timed_out']) {
				return true;
			}
			return false;
		}

		/**
		 * first function to call after constructor
		 * if connection succeeds, it will return true, and other methods can be called
		 */
		public function connect($time_out = CLIENT_CONNECT_TIMEOUT) {
			$this->sock = @fsockopen($this->ip, $this->port, $errno, $errstr, $time_out);
			if (!$this->sock) {
				@fclose($this->sock);
				return false;
			};
			stream_set_blocking($this->sock, true);
			stream_set_timeout($this->sock, CLIENT_CMD_TIMEOUT);
			return true;
		}

		/**
		 * send/rcv example
		 */
		public function get_data_from_command() {
			$this->write('get_data');
			$res = $this->read();
			if ($this->is_timed_out()) return false;
			else return $res;
		}

		public function close() {
			@fclose($this->sock);
		}
	};

?>
