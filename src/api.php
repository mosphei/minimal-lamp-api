<?php
require './config.php'; 
define("FUTURE_TIME",'9999-12-31');
$data;
//main
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$input=json_decode($HTTP_RAW_POST_DATA);
	$doc=$input->doc;
	$table=preg_replace('/[^a-zA-Z0-9]/','',$input->table);
	$data= array('table' => $table, 'messages' =>'','doc'=>$doc);
	save_doc($doc,$table);
} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$data['messages']='GET "' . $_GET['_id'] . '" from '. $_GET['table'];
	$id=$_GET['_id'];
	$table=$_GET['table'];
	$data['doc']=get_doc($id,$table);
}

header('Content-Type: application/json');
echo json_encode($data);

function save_doc($doc,$table) {
	global $pdo, $data;
	$old_doc = get_doc($doc->_id,$table);
	$data['old_doc'] = $old_doc;
	//echo json_encode($old_doc);
	if (!$old_doc && !$doc->_rev) {
		//insert
		$sql="insert into $table (_id,_rev,doc,valid_from,valid_to) values(?,?,?,now(),?)";
		$data['messages'] .= "new doc\nsql \"$sql\"";
		$docstring=json_encode($doc);
		
		$rev='1-' . sha1($docstring);
		
		$pdo->prepare($sql)->execute([
			$doc->_id,
			$rev,
			json_encode($doc),
			FUTURE_TIME
		]);
		$data['_rev']=$rev;
		$data['_id']=$doc->_id;
	} else if ($old_doc->_rev == $doc->_rev) {
		//update
		http_response_code(501);
		echo 'update ';
	} else {
		http_response_code(409);
		$data['http_response_code']=http_response_code();
		$data['error'] = '_rev does not match';
	}
}
function get_doc($id,$table) {
	global $pdo, $data;
	try {
		$retval;
		$sql="select * from $table where _id = ? and valid_from <= now() and valid_to > now()";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$id]);
		while ($row = $stmt->fetch()) {
			$retval = json_decode($row['doc']);
			$retval->_id=$row['_id'];
			$retval->_rev=$row['_rev'];
		}
		return $retval;
	} catch (PDOException $e) {
		if ($e->getCode() == '42S02') {
			$data->messages="create table $table\n";
			$sql="create table $table(_id varchar(255) not null,_rev varchar(255) not null,doc longtext,valid_from datetime not null,valid_to datetime not null, primary key (_id,valid_to))";
			$pdo->exec($sql);
		}
	}
}