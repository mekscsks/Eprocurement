<?php include("includes/sidebar.php") ?>
<?php include("includes/header.php") ?>

<div id="content" class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <h4 class="mb-3 text-center">Available for Bid</h4>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="bidsTable" class="container px-4 lg:px-8 mx-auto text-gray-700 overflow-x-hidden overflow-hidden dark:bg-[#2f8359]">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Starting Bid</th>
                                    <th>Highest Bid</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Vintage Guitar</td>
                                    <td>Classic acoustic guitar</td>
                                    <td>₱5,000</td>
                                    <td>₱6,500</td>
                                    <td>2026-01-20</td>
                                    <td><span class="badge bg-success">Open</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-gavel"></i> Bid
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Studio Microphone</td>
                                    <td>Condenser mic (brand new)</td>
                                    <td>₱3,000</td>
                                    <td>₱3,800</td>
                                    <td>2026-01-22</td>
                                    <td><span class="badge bg-success">Open</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-gavel"></i> Bid
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>DJ Controller</td>
                                    <td>2-channel USB controller</td>
                                    <td>₱8,000</td>
                                    <td>₱9,200</td>
                                    <td>2026-01-25</td>
                                    <td><span class="badge bg-warning text-dark">Ending Soon</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-gavel"></i> Bid
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>4</td>
                                    <td>Sound Mixer</td>
                                    <td>8-channel analog mixer</td>
                                    <td>₱6,000</td>
                                    <td>₱6,000</td>
                                    <td>2026-01-18</td>
                                    <td><span class="badge bg-danger">Closed</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            Closed
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Move Font Awesome to head in real project -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
