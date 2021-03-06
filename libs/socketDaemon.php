<?php
class socketDaemon {
	public $servers = array();
	public $clients = array();

	public function create_server($server_class, $client_class, $bind_address = 0, $bind_port = 0)
	{
		$server = new $server_class($client_class, $bind_address, $bind_port);
		if (!is_subclass_of($server, 'socketServer')) {
			throw new socketException("Invalid server class specified! Has to be a subclass of socketServer");
		}
		$this->servers[(int)$server->socket] = $server;
		return $server;
	}

	public function create_client($client_class, $remote_address, $password, $remote_port, $bind_address = 0, $bind_port = 0)
	{
		$client = new $client_class($bind_address, $password, $bind_port);
		if (!is_subclass_of($client, 'socketClient')) {
			throw new socketException("Invalid client class specified! Has to be a subclass of socketClient");
		}
		$client->set_non_block(true);
		$client->connect($remote_address, $remote_port);
		$this->clients[(int)$client->socket] = $client;
		return $client;
	}

	private function create_read_set()
	{
		$ret = array();
		foreach ($this->clients as $socket) {
			$ret[] = $socket->socket;
		}
		foreach ($this->servers as $socket) {
			$ret[] = $socket->socket;
		}
		return $ret;
	}

	private function create_write_set()
	{
		$ret = array();
		foreach ($this->clients as $socket) {
			if (!empty($socket->write_buffer) || $socket->connecting) {
				$ret[] = $socket->socket;
			}
		}
		foreach ($this->servers as $socket) {
			if (!empty($socket->write_buffer)) {
				$ret[] = $socket->socket;
			}
		}
		return $ret;
	}

	private function create_exception_set()
	{
		$ret = array();
		foreach ($this->clients as $socket) {
			$ret[] = $socket->socket;
		}
		foreach ($this->servers as $socket) {
			$ret[] = $socket->socket;
		}
		return $ret;
	}

	private function clean_sockets()
	{
		foreach ($this->clients as $socket) {
			if ($socket->disconnected || !is_resource($socket->socket)) {
				if (isset($this->clients[(int)$socket->socket])) {
					unset($this->clients[(int)$socket->socket]);
				}
			}
		}
	}

	private function get_class($socket)
	{
		if (isset($this->clients[(int)$socket])) {
			return $this->clients[(int)$socket];
		} elseif (isset($this->servers[(int)$socket])) {
			return $this->servers[(int)$socket];
		} else {
			throw (new socketException("Could not locate socket class for $socket"));
		}
	}

	public function process()
	{
		// if socketClient is in write set, and $socket->connecting === true, set connecting to false and call on_connect
		$read_set      = $this->create_read_set();
		$write_set     = $this->create_write_set();
		$exception_set = $this->create_exception_set();
		$event_time    = time();
		while (($events = socket_select($read_set, $write_set, $exception_set, 2)) !== false) {
			if ($events > 0) {
				foreach ($read_set as $socket) {
					$socket = $this->get_class($socket);
					if (is_subclass_of($socket,'socketServer')) {
						try {
							$client = $socket->accept();
						} catch (socketException $e) {
							echo "Error accepting connection : ".$e->getMessage()."\n";
						}
						$this->clients[(int)$client->socket] = $client;
					} elseif (is_subclass_of($socket, 'socketClient')) {
						// regular on_read event
						$socket->read();
					}
				}
				foreach ($write_set as $socket) {
					$socket = $this->get_class($socket);
					if (is_subclass_of($socket, 'socketClient')) {
						if ($socket->connecting === true) {
							$socket->on_connect();
							$socket->connecting = false;
						}
						$socket->do_write();
					}
				}
				foreach ($exception_set as $socket) {
					$socket = $this->get_class($socket);
					if (is_subclass_of($socket, 'socketClient')) {
						$socket->on_disconnect();
						if (isset($this->clients[(int)$socket->socket])) {
							unset($this->clients[(int)$socket->socket]);
						}
					}
				}
			}
			if (time() - $event_time > 1) {
				// only do this if more then a second passed, else we'd keep looping this for every bit recieved
				foreach ($this->clients as $socket) {
					$socket->on_timer();
				}
				$event_time = time();
			}
			$this->clean_sockets();
			$read_set      = $this->create_read_set();
			$write_set     = $this->create_write_set();
			$exception_set = $this->create_exception_set();
		}
	}
}
