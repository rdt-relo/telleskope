<?php
require_once __DIR__.'/head.php';
$message = NULL;
if( isset($_POST['submit'])){
	
    if ( isset($_FILES["file"])) {    
            //if there was an error uploading the file
            if ($_FILES["file"]["error"] > 0) {
                $message = "Please choose a file.";
                
            } else {
                    if( !empty($_FILES["file"]) ){             
                        $file_extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
                        $allowed_image_extension = array(
                            "csv",
                            "tsv"                        
                        );

                        if( ! in_array($file_extension,$allowed_image_extension)){                         
                            $message = "Upload valid file. Only .csv and .tsv are allowed.";
                        } // Validate file size
                        else if (($_FILES["file"]["size"] > 900000000)) {
                            $message = "File size exceeds 900MB.";
                        }else {                            

                            $handle = fopen($_FILES['file']['tmp_name'], "r");
                            $delimiter = ($file_extension == 'tsv') ? "\t" : ',';
                            $headers = fgetcsv($handle, 8192, $delimiter);
                            $cols = count($headers);
                            while (($data_row = fgetcsv($handle, 8192, $delimiter)) !== FALSE) 
                            {
                                if ( $cols != count($data_row)) {                                    
                                    $message = "There is some issue with file."; 
                                    break;                         
                                }
                                
                            }
                            if(!$message){
                                if( $_POST['userDataSelectField'] == "user-data-sync"){     
                                $retVal = $_COMPANY->saveFileInUploader($_FILES['file']['tmp_name'], 'manual_'.$_FILES["file"]["name"], 'user-data-sync');
                                if($retVal){                           
                                        $message = "File uploaded successfully."; 
                                }

                                }else{                                               
                                    $retVal = $_COMPANY->saveFileInUploader($_FILES['file']['tmp_name'], 'manual_'.$_FILES["file"]["name"], 'user-data-delete');
                                    if($retVal){
                                        $message = "File uploaded successfully.";                           
                                    }
                                }

                                fclose($handle);    
                            }                       
                        } 
                                    
                    }
            }
    }
 } //Submit
 include(__DIR__ . '/views/header.html');
?>

<div class="container col-md-offset-2 margin-top">
    <div class="row">
        <div class="col-md-12">
            <div class="widget-simple-chart card-box">

                <div class="col-md-12 divider"><h6>HRIS Upload</h6></div>

                <div class="col-md-7">
                    <form class="form-horizontal" method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?=Session::GetInstance()->csrf;?>">
                        
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Section *</label>
                            <div class="col-lg-9">
                                <select name="userDataSelectField" class="form-control">
                                    <option value="user-data-sync" selected="">User Data Sync</option>
                                    <option value="user-data-delete">User Data Delete</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">File Upload *</label>
                            <div class="col-lg-9">
                                <input type="file" class="text-box ignore" id="hris_file" name="file" accept=".csv,.tsv">
                                <div style="color:grey;font-size: small;">Only comma seperated values (.csv) or tab seperated values (.tsv) formats are allowed</div>
                           </div>
                        </div>
                           
                        <div class="form-group">
                            <label class="col-lg-3 control-label"></label>
                            <div class="col-lg-8">                                
                                <button type="submit" id="hris_file_submit" name="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>

               
            </div>
        </div>
    </div>
</div>

<?php if($message){?>

    <script>
        swal.fire({title: 'Message',text:'<?php echo $message;?>'})
    </script>

 <?php } ?>

</body>
</html>
