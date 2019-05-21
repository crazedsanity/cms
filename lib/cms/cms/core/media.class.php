<?php

namespace cms\cms\core;

use \PDOException;
use \Exception;


class media extends core {
	protected $db;
	
	protected $defaults = array();
	
	const ORPHANFOLDER = "__NO_PARENT__";
	const CHILDFOLDER = "_children";
	
	const INVALIDMIME=1;
	const MISSINGRECORD = 2;
	const MISSINGFOLDER = 3;
	const DELETE_ERR_DB_CONSTRAINT = 4;
	
	public function __construct($db) {
		parent::__construct($db, 'media', 'media_id');
		$this->db = $db;
	}
	
	
	public function createFolder($path, $displayName) {
		$sql = "INSERT INTO media_folders (path, display_name) VALUES 
			(:path, :display)";
		$params = array(
			'path'		=> $path,
			'display'	=> $displayName,
		);
		$newId = $this->db->run_insert($sql, $params);
		
		return $newId;
	}
	
	
	
	public function getAll() {
		
		$sql = "
			SELECT 
				 m.*
				,f.path
				,f.display_name
			FROM 
				media AS m
				LEFT OUTER JOIN media_folders as f ON (m.media_folder_id=f.media_folder_id)
			ORDER BY
				m.filename, f.path";
		
		
		return $this->db->fetch_array_assoc($sql, 'media_id');
	}
	
	
	public function getAll_structured() {
		$media = $this->getAll();
		$structured = $this->getFolderList();
		$this->debugPrint($structured, "Folder stuff");
		$this->debugPrint($media, "Media");
		
		foreach($media as $id=>$data) {
			$folderId = $data['media_folder_id'];
			if(isset($structured[$folderId])) {
				$structured[$folderId][self::CHILDFOLDER][$id] = $data;
			}
			else {
				$structured[$folderId] = array(
					'display_name'	=> self::ORPHANFOLDER,
					self::ORPHANFOLDER	=> array(
						$id	=> $data
					)
				);
			}
		}
		
		return $structured;
	}
	
	
	
	public function get($id) {
		// Must be an outer join, because data integrity in MySQL is virtually non-existent.
		$sql = "
			SELECT 
				 m.*
				,f.path
				,f.display_name
			FROM 
				media AS m
				LEFT OUTER JOIN media_folders as f ON (m.media_folder_id=f.media_folder_id)
			WHERE
				m.media_id=:id
			ORDER BY
				m.filename, f.path";
		
		$params = array(
			'id'	=> $id,
		);
		$this->db->run_query($sql, $params);
		
		return $this->db->get_single_record();
	}
	
	
	
	public function getFolderList() {
		$sql = "SELECT * FROM media_folders ORDER BY display_name, path";
		$this->db->run_query($sql);
		return $this->db->farray_fieldnames('media_folder_id');
	}
	
	
	
	public function getFolderOptionList($selected=null) {
		$retval = '';
		$folders = $this->getFolderList();
    
        foreach($folders as $folder){
            $retval .= '<option';
            if($folder['media_folder_id']==$selected || $folder['path'] == $selected){
                $retval .= ' selected="selected" ';
            }
            $retval .= ' value="'.$folder['media_folder_id'].'">'.$folder['display_name'].'</option>';
        }
		
		return $retval;
	}
	
	
	
	public function codeToMessage($code) {
		switch ($code) {
			case UPLOAD_ERR_INI_SIZE:
				$message = "the uploaded file exceeds the upload_max_filesize directive in php.ini";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = "the uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = "the uploaded file was only partially uploaded";
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = "no file was uploaded";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = "missing a temporary folder";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = "failed to write file to disk, this probably indicates a permissions issue on the server";
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = "file upload stopped by extension";
				break;

			default:
				$message = "unknown upload error";
				break;
		}
		return $message;
	}
	
	
	
	public function delete($id) {
		$this->debugPrint(func_get_args(), "arguments");
		$result = 0;
		$data = $this->get($id);
		if(is_array($data)) {
			$this->debugPrint($data, "record");
			try {
				$result += $this->deleteRecord($id);
				
			} catch (PDOException $ex) {
				//get the details.
				$msg = $ex->getMessage();
				
				if(preg_match('~cannot delete or update a parent row~i', $msg)) {
					throw new Exception($ex->getMessage(), self::DELETE_ERR_DB_CONSTRAINT, $ex);
				}
				// nothin' doin'.  Throw it again.
				throw $ex;
			}
			$filePath = ROOT .'/'. $data['path'] .'/'. $data['filename'];
			$this->debugPrint($filePath, "path to file");
			$result += $this->deleteFile($filePath);
		}
		
		return $result;
	}
	
	
	
	protected function deleteFile($realPath) {
		$result = 0;
		if(file_exists($realPath)) {
			if(unlink($realPath)) {
				$result = 1;
			}
		}
		return $result;
	}
	
	
	protected function deleteRecord($id) {
		$sql = "DELETE FROM media WHERE media_id={$id}";
		try {
			$result = $this->db->query($sql);
		} catch (Exception $ex) {
			$details = "ERROR: ". $ex->getMessage();
			if(preg_match('~integrity constraint violation~i', $ex->getMessage())) {
				$details = "record ID #(". $id .") is referenced in another table";
			}
			throw new Exception($details, self::DELETE_ERR_DB_CONSTRAINT, $ex);
		}
		
		return $result;
	}
	
	
	
	protected function create(array $fieldToValue) {
		$result = $this->db->insert('media', $fieldToValue);
		return $result;
	}
	
	
	/**
	 * @param (str) $index			name in $_FILES to find the image info
	 * @param (nul) $targetPath		(unused) path relative to DOCUMENT_ROOT where image will be uploaded
	 * @param (arr) $insertData		Array of data for insert/update
	 * @param (str) $type			insert (new) or update (existing); use 'update' if unsure
	 * @return (int)				media_id created/updated
	 * @throws Exception
	 * @throws \InvalidArgumentException
	 * To make simple filenames that can be found __quickly__ on the server, and 
	 * no worries about translating filenames:
	 *  
	 *  1. filename in database is there for display value ONLY (for admins)
	 *  2. database record created FIRST
	 *  3. media_id + file_extension of record used for actual filename (e.g. "52.jpg")
	 * 
	 * If (for bizarre reasons) someone is worried about users getting all the 
	 * files just by scanning for them, a script can be used to "pretend" that 
	 * it is a folder component, and can translate a full path to an ID, like:
	 *  http://foo.com/data/{uploads}/media/myFancyFilename%20here.jpG
	 * 
	 * So "uploads" is really a script; apache just rewrites the URL, so the 
	 * actual request is 
	 *
	 * http://foo.com/data/uploads.php?file=myFanceFilename%20here.jpG
	 * 
	 * That script either outputs the file (with appropriate mime type) or gives 
	 * a 404 not found.
	 * 
	 * Translating from an ID back to a filename seems like an unnecessary step.
	 * 
	 *
	 * TODO: integrate antivirus/malicious script checker
	 * TODO: stop handling $targetPath as an argument (it comes from the path based on media_folder_id)
	 */
	public function upload($index, $targetPath, array $insertData, $type='update') { #, $title, $targetPath, $adminId, $user) {
		if(is_array($_FILES) && isset($_FILES[$index]) && $_FILES[$index]['error'] == 0) {
			// TODO: check if it's an allowed file type.
			$pathinfo = pathinfo($_FILES[$index]['name']);
//			$allowed = $this->getAllowedMimes();
//			if(isset($allowed[$pathinfo['extension']])) {
				$displayFilename = self::cleanString($_FILES[$index]['name']);
				if(isset($insertData['display_filename'])) {
					$displayFilename = $insertData['display_filename'];
				}
				else {
					$insertData['display_filename'] = $displayFilename;
				}
				
				$insertData['filename'] = strtolower($pathinfo['extension']);
				$insertData['filesize'] = $_FILES[$index]['size'];
				$insertData['filetype'] = $_FILES[$index]['type'];
				
				if(!isset($insertData['media_folder_id']) || intval($insertData['media_folder_id']) == 0) {
					$insertData['media_folder_id'] = $this->getDefaultFolderId();
					$targetPath = $this->defaults['path'];
				}
				else {
					$targetPath = $this->getFolderPath($insertData['media_folder_id']);
				}
				
				if($type == 'update' && (!isset($insertData[$this->pkey]) || !intval($insertData[$this->pkey]))) {
					$this->debugPrint($insertData, "missing ID, switching to an insert");
					$type = 'insert';
				}
				
				try {
					switch($type) {
						case 'update':
							if(isset($insertData[$this->pkey]) && intval($insertData[$this->pkey])) {
								$id = $insertData[$this->pkey];
								unset($insertData[$this->pkey]);
								$insertData['filename'] = $id .'.'. strtolower($pathinfo['extension']);
								$updateRes = $this->update($insertData, $id);
								$this->deleteFile(ROOT . $targetPath .'/'. $id .'.'. strtolower($pathinfo['extension']));

								$realFile = $id .'.'. strtolower($pathinfo['extension']);
								$fullNewPath = ROOT . $targetPath .'/'. $realFile;
								$moveRes = move_uploaded_file($_FILES[$index]['tmp_name'], $fullNewPath);
							}
							else {
								$this->debugPrint($insertData, "Record info");
								throw new Exception(__METHOD__ ." - could not find ID");
							}
							break;
						case 'insert':
						default:
							// show it as a failed upload, just in case there's an epic fail later.
							$insertData['display_filename'] = '(failed upload)';
							
							$id = $this->create($insertData);
							$realFile = $id .'.'. strtolower($pathinfo['extension']);
							$fullNewPath = ROOT . $targetPath .'/'. $realFile;
							$moveRes = move_uploaded_file($_FILES[$index]['tmp_name'], $fullNewPath);
							
							
							if($moveRes) {
								$changedData = array(
									'display_filename'	=> $displayFilename,
									'filename'			=> $realFile,
								);
								try {
									//
									debugPrint($changedData, "Data to change");
									$updateRes = $this->update($changedData, $id);
									debugPrint($updateRes, "Result of updating after moving file");
								}
								catch(Exception $ex) {
									// TODO: log this!
									throw new Exception(__METHOD__ ." - failed to update after moving file");
								}
							}
							else {
								try {
									$this->deleteRecord($id);
								} catch (Exception $ex) {
									// TODO: log this!
								}
								// TODO: log this!
								throw new Exception(__METHOD__ ." - failed to move file... ");
							}
					}
				} catch (Exception $ex) {
					// TODO: log this!
					throw $ex;
				}
				
//			}
//			else {
//				throw new Exception("invalid mime type", self::INVALIDMIME, null);
//			}
		}
		else {
			$exceptionDetails = "no files uploaded";
			if(isset($_FILES[$index]) && $_FILES[$index]['error'] != 0) {
				$exceptionDetails = $this->codeToMessage($_FILES[$index]['error']);
			}
			throw new \InvalidArgumentException($exceptionDetails);
		}
		
		return $id;
	}
	

	public static function cleanString($string) {
		$string = trim($string);
		$string = str_replace(' ', '-', $string);
		$string = strtolower($string);
		$string = preg_replace('#[^a-z0-9\s-_.]#i', '', $string);

		return $string;
	}
	
	
	
	public static function clean_name($title) {
		$pathinfo = pathinfo($title);
		$bits = explode('.', $pathinfo['basename']);
		array_pop($bits);
		$newTitle = implode('.', $bits);
		$retval = strtolower(trim(preg_replace('/[^a-zA-Z0-9.-]+/', '-', $newTitle), '-'));
		return $retval .'.'. $pathinfo['extension'];
	}
	
	
	
	public function getByName($path) {
		$info = pathinfo($path);
		
		$dir = self::translate_path($info['dirname'], true);
		$originalFile = $info['basename'];
		$file = self::clean_name($info['basename']);
		
		$sql = "SELECT * FROM media WHERE (display_filename='{$file}' OR display_filename='{$originalFile}') AND path='{$dir}' LIMIT 1";
		
		return $this->db->query_first($sql);
	}
	
	
	
	public function getFolderIdFromPath($path) {
		$fixedPath = $this->cleanPath($path);
		
		$id=$this->_folderIdFromPath($fixedPath);
		
		if($id === false) {
			$bits = explode('/', $fixedPath);
			
			while(count($bits) && $id === false) {
				array_shift($bits);
				$fixedPath = implode('/', $bits);
				
				$id = $this->_folderIdFromPath($fixedPath);
			}
			
			if($id === null) {
				throw new Exception("could not locate folder", self::MISSINGFOLDER);
			}
		}
		
		return $id;
	}
	
	
	
	protected function _folderIdFromPath($path) {
		$sql = "SELECT * FROM media_folders WHERE path=:path ORDER BY media_folder_id, path";
		$params = array(
			'path'	=> $path,
		);
		$this->db->run_query($sql, $params);
		$data = $this->db->farray();
		
		return $data[0];
	}
	
	
	
	public function cleanPath($path) {
		$stripThis = array(
			'~^/',
			'~/$~',
		);
		$cleanedPath = preg_replace($stripThis, '', $path);
		return $cleanedPath;
	}
	
	
	public function search($orderBy, array $constraints, $searchCrit) {
		$this->debugPrint(func_get_args(), "args");
		$sql = "
			SELECT
				 m.media_id
				,mt.media_tag_id
				,m.display_filename
				,m.filesize
				,m.media_folder_id
				,m.filetype
				,m.date_created
				,m.date_modified
				,mf.path
				,mf.display_name AS folder_display_name
				,t.tag_id
				,t.tag_name
				,t.tag_type_id
				,tt.tag_type_name
			FROM 
				media AS m
				INNER JOIN media_folders AS mf ON (m.media_folder_id=mf.media_folder_id)
				INNER JOIN media_tags AS mt ON (m.media_id=mt.media_id)
				INNER JOIN tags AS t ON (mt.tag_id=t.tag_id)
				INNER JOIN tag_types AS tt ON (t.tag_type_id=tt.tag_type_id)
				";
		
		$params = array();
		
		$constraintSql = '';
		if(is_array($constraints)) {
			foreach($constraints as $k=>$v) {
				$paramKey = preg_replace('~\.~', '_', $k);
				$constraintSql = ToolBox::create_list($constraintSql, "{$k} LIKE :{$paramKey}", " AND ");
				$params[$paramKey] = $v;
			}
			$sql .= ' WHERE '. $constraintSql;
		}
		
		$fieldsToSearch = array(
			'm.display_filename',
			't.tag_name',
		);
		
		$searchSql = '';
		$i=0;
		foreach($fieldsToSearch as $field) {
			$myParam = 'search'. $i++;
			$searchSql = ToolBox::create_list($searchSql, $field .' LIKE :'. $myParam, ' OR ');
			$params[$myParam] = '%'. $searchCrit .'%';
		}
		
		$sql .= " AND
			({$searchSql})
			";
		
		if(!is_null($orderBy)) {
			$sql .= " ORDER BY {$orderBy}";
		}
		elseif(!is_null($this->defaultOrder)) {
			$sql .= " ORDER BY {$this->defaultOrder}";
		}
		
		$this->debugPrint($sql, "SQL");
		$this->debugPrint($params, "Parameters");
		$data = array();
		
		try {
			$this->db->run_query($sql, $params);
			$sData = $this->db->farray_fieldnames('media_tag_id');
			
			// only return unique data
			foreach($sData as $k=>$v) {
				if(!isset($data[$v['media_id']])) {
					$data[$v['media_id']] = $v;
				}
			}
			
		} catch (Exception $ex) {
			$this->debugPrint($ex->getMessage(), "Exception");
			throw $ex;
		}
		return $data;
	}
	
	
	public function getByFilename($filename) {
		if(!empty($filename)) {
			$sql = "SELECT m.*, mf.* FROM media AS m INNER JOIN media_folders AS mf"
					. " ON m.media_folder_id=mf.media_folder_id"
					. " WHERE m.filename=:file";
			$params = array(
				'file'	=> $filename,
			);
			$this->debugPrint($sql, "sql");
			$this->debugPrint($params, "parameters");
			$this->db->run_query($sql, $params);
		}
		else {
			throw new \InvalidArgumentException;
		}
		return $this->db->get_single_record();
	}
	
	
	public function getDefaultFolderId() {
		$mfObj = new mediaFolder($this->db);
		$allFolders = $mfObj->getAll();
		if(count($allFolders) > 0) {
			$this->defaults =  array_shift($allFolders);
			$folderId = $this->defaults['media_folder_id'];
		}
		else {
			throw new \Exception("No media folders found");
		}
		return $folderId;
	}
	
	
	public function getFolderPath($mediaFolderId) {
		$mfObj = new mediaFolder($this->db);
		$data = $mfObj->get($mediaFolderId);
		
		$this->debugPrint($data, "media folder data",1);
		
		return($data['path']);
	}
}
