<!-- Add New Procurement Modal -->
<div class="modal fade" id="addProcurementModal" tabindex="-1" aria-labelledby="addProcurementModalLabel" aria-hidden="true" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addProcurementModalLabel">Add New Procurement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="functions/procurementfunctions.php" method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="proc_title" placeholder="Enter procurement title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode of Procurement</label>
                        <select class="form-select" name="proc_mode" required>
                            <option value="" disabled selected>Select Mode</option>
                            <option value="Public Bidding">Public Bidding</option>
                            <option value="Direct Contracting">Direct Contracting</option>
                            <option value="Limited Source Bidding">Limited Source Bidding</option>
                            <option value="Alternative Methods">Alternative Methods</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nature of Procurement</label>
                        <select class="form-select" name="proc_nature" required>
                            <option value="" disabled selected>Select Nature</option>
                            <option value="Goods">Goods</option>
                            <option value="Infrastructure">Infrastructure</option>
                            <option value="Consulting Services">Consulting Services</option>
                            <option value="Other Services">Other Services</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approved Budget for the Contract (ABC)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="proc_budget" placeholder="Enter budget amount" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Published</label>
                        <input type="date" class="form-control" name="proc_date_published" required>
                    </div>
                    <h6 class="mt-4">
                        <a class="btn btn-link" data-bs-toggle="collapse" href="#scheduleSection" role="button">Schedule (Optional)</a>
                    </h6>
                    <div class="collapse" id="scheduleSection">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pre-Bid Conference</label>
                                <input type="date" class="form-control" name="proc_prebid">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Deadline for Submission</label>
                                <input type="date" class="form-control" name="proc_deadline">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bid Opening</label>
                                <input type="date" class="form-control" name="proc_bid_opening">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lot Table Reference</label>
                        <input type="text" class="form-control" name="proc_lot_table" placeholder="Enter lot table reference">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="proc_description" rows="3" placeholder="Enter detailed description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Winning Bidder</label>
                        <input type="text" class="form-control" name="proc_winner" placeholder="Enter winning bidder if known">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="proc_status" required>
                            <option value="Open" selected>Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bidding Document (PDF only)</label>
                        <input type="file" class="form-control" name="proc_bidding_doc" accept=".pdf">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Document (PDF only)</label>
                        <input type="file" class="form-control" name="proc_additional_doc" accept=".pdf">
                    </div>
                    <button type="submit" name="add_procurement" class="btn btn-success">Add Procurement</button>

                </form>
            </div>
        </div>
    </div>
</div>
