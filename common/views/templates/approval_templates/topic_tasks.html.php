<?php foreach($approvalTasks as $task){
                        
                        $statusClass = '';
                        $statusText = '';
                        
                        switch($task['approval_status']){
                            case 'denied':
                                $statusClass="bg-danger";
                                $statusText=" Denied";
                                break;
                            case 'approved':
                                $statusClass="bg-success";
                                $statusText=" Completed";
                                break;    
                            case 'processing':
                                $statusClass="bg-warning";
                                $statusText=" In Process";
                                break;
                            case 'skipped':
                                $statusClass="bg-info";
                                $statusText=" Not Needed";
                                break;   
                            default:
                                $statusClass="bg-danger";
                                $statusText=" Not started";
                                                                                               
                        }
                        
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card approval-card-box">
                                <div class="card-body" style="text-align: left;">
                                    <div class="card-text mt-2" style="display:block; mb-3">
                                        <h6 class="card-title mb-1">Task Name:</h6>
                                        <span class="task-tile"> <?= $task['approval_task_name']; ?> <small>&nbsp;(stage <?=$task['approval_stage']?>)</small></span>
                                        <?php if($task['approval_status']==='approved'){
                                            $modified_by = $task['modifiedby'] ? USER::GetUser($task['modifiedby']) : '';
                                            $fullName = $modified_by ? $modified_by->getFullName() : '';
                                            ?>
                                            <h6 class="mt-3 mb-1">Task Approved By:</h6><span class="task-tile"><?= $fullName; ?></span>
                                        <?php }else{ 
                                            $assigned_to = $task['assigned_to'] ? USER::GetUser($task['assigned_to']) : '';
                                            $fullName = $assigned_to ? $assigned_to->getFullName() : 'Not Assigned';?>
                                            <h6 class="mt-3 mb-1">Task Assigned to:</h6><span class="task-tile"> <?= $fullName ?> </span>
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="progress mt-3">
                                        <div class="progress-bar <?= $statusClass ?>" role="progressbar" style="width: 100%;" aria-volumein="0" aria-volumenow="100" aria-volumemax="100"> <?= ucfirst($statusText); ?> </div>
                                    </div>
                                </div>
                            </div> 
                        </div>
                    <?php } ?>