<h1>Job Applications - Cleanup</h1>

<div class="settings_panel">
    <table class="form-table settings parent-settings">
        <tbody>
            <tr>
                <th scope="row">
                    <label>Count duplicates in DB</label>
                </th>
                <td>
                    <form method="post">
                        <input type="submit" name="job_applications_cleanup_count_submit" class="button" value="Count Duplicates" />
                        <?php if ( isset( $_POST[ 'job_applications_cleanup_count_submit' ] ) ) {
                                echo $this->count_duplicates_in_db();
                            }
                        ?>                       
                    </form>
                    <p class="description">Counts the total number of duplicate job applications in the database.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>Delete duplicates in DB</label>
                </th>
                <td>
                    <form method="post">
                        <input type="submit" name="job_applications_cleanup_delete_submit" class="button" value="Delete Duplicates" />
                        <?php if ( isset( $_POST[ 'job_applications_cleanup_delete_submit' ] ) ) {
                                echo $this->remove_duplicates_from_db();
                            }
                        ?> 
                    </form>
                    <p class="description">Deletes the duplicate job applications and their relative meta data from the database.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label>Delete unsed Folders</label>
                </th>
                <td>
                    <form method="post">
                        <input type="submit" name="job_applications_cleanup_unused_files_submit" class="button" value="Delete Folders" />
                        <?php if ( isset( $_POST[ 'job_applications_cleanup_unused_files_submit' ] ) ) {
                                echo $this->remove_unused_folders();
                            }
                        ?> 
                    </form>
                    <p class="description">This deletes the unsed folders stored on the file system which are not referenced from the database.</p>
                </td>
            </tr>
        </tbody>
    </table>        
</div>