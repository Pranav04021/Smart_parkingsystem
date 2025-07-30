<?php 
$page_title = "Help & Support - Smart Parking";
include 'includes/header.php'; 

$faqs = [
    [
        'question' => 'How do I cancel a reservation?',
        'answer' => 'You can cancel your reservation up to 1 hour before your scheduled time through the "My Reservations" page. Simply click on the reservation and select "Cancel". Cancellations made within 1 hour may incur a fee.'
    ],
    [
        'question' => 'What payment methods are accepted?',
        'answer' => 'We accept all major credit cards (Visa, MasterCard, American Express, Discover), debit cards, and digital wallets like Apple Pay and Google Pay. You can also use your Smart Parking wallet balance.'
    ],
    [
        'question' => 'How do I extend my parking time?',
        'answer' => 'You can extend your parking time through the mobile app or website while your reservation is active. Extensions are subject to availability and will be charged at the current hourly rate.'
    ],
    [
        'question' => 'What happens if I\'m late to my reservation?',
        'answer' => 'We hold your spot for up to 15 minutes past your reservation time. After that, the spot may be released to other customers. Late arrivals may incur additional fees.'
    ],
    [
        'question' => 'Can I modify my reservation?',
        'answer' => 'Yes, you can modify your reservation up to 2 hours before your scheduled time. Changes to date, time, or duration are subject to availability and may affect pricing.'
    ],
    [
        'question' => 'How do I get a receipt for my parking?',
        'answer' => 'Receipts are automatically sent to your registered email address after each transaction. You can also view and download receipts from your account dashboard or the "My Reservations" section.'
    ]
];
?>

<div class="page-header">
    <h1><i class="fas fa-question-circle"></i> Help & Support</h1>
    <p>Find answers to common questions or get in touch with our support team.</p>
</div>

<!-- Quick Help Cards -->
<div class="help-cards">
    <div class="help-card">
        <div class="help-icon bg-blue">
            <i class="fas fa-book"></i>
        </div>
        <h3>User Guide</h3>
        <p>Step-by-step instructions for using Smart Parking</p>
        <button class="btn btn-outline" onclick="openUserGuide()">
            View Guide
        </button>
    </div>
    
    <div class="help-card">
        <div class="help-icon bg-green">
            <i class="fas fa-video"></i>
        </div>
        <h3>Video Tutorials</h3>
        <p>Watch helpful videos on how to use our features</p>
        <button class="btn btn-outline" onclick="openVideoTutorials()">
            Watch Videos
        </button>
    </div>
    
    <div class="help-card">
        <div class="help-icon bg-orange">
            <i class="fas fa-comments"></i>
        </div>
        <h3>Live Chat</h3>
        <p>Chat with our support team in real-time</p>
        <button class="btn btn-primary" onclick="startLiveChat()">
            Start Chat
        </button>
    </div>
    
    <div class="help-card">
        <div class="help-icon bg-purple">
            <i class="fas fa-envelope"></i>
        </div>
        <h3>Email Support</h3>
        <p>Send us a detailed message about your issue</p>
        <button class="btn btn-outline" onclick="openEmailSupport()">
            Send Email
        </button>
    </div>
</div>

<!-- Contact Information -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-phone"></i> Contact Information</h3>
    </div>
    <div class="card-body">
        <div class="contact-methods">
            <div class="contact-method">
                <div class="contact-icon bg-blue">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="contact-info">
                    <h4>Phone Support</h4>
                    <p>1-800-PARKING (1-800-727-5464)</p>
                    <small>Available 24/7</small>
                </div>
            </div>
            
            <div class="contact-method">
                <div class="contact-icon bg-green">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-info">
                    <h4>Email Support</h4>
                    <p>support@smartparking.com</p>
                    <small>Response within 24 hours</small>
                </div>
            </div>
            
            <div class="contact-method">
                <div class="contact-icon bg-orange">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="contact-info">
                    <h4>Live Chat</h4>
                    <p>Available on website and mobile app</p>
                    <small>Average response: 2 minutes</small>
                </div>
            </div>
            
            <div class="contact-method">
                <div class="contact-icon bg-purple">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="contact-info">
                    <h4>Office Address</h4>
                    <p>123 Smart Street, Tech City, TC 12345</p>
                    <small>Monday - Friday, 9 AM - 6 PM</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
    </div>
    <div class="card-body">
        <div class="faq-search">
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search FAQs..." id="faqSearch" onkeyup="searchFAQs()">
            </div>
        </div>
        
        <div class="faq-list" id="faqList">
            <?php foreach ($faqs as $index => $faq): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(<?php echo $index; ?>)">
                    <h4><?php echo $faq['question']; ?></h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer" id="faq-<?php echo $index; ?>">
                    <p><?php echo $faq['answer']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Support Ticket Form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-ticket-alt"></i> Submit Support Ticket</h3>
    </div>
    <div class="card-body">
        <form id="supportTicketForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" class="form-control" placeholder="Brief description of your issue" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select class="form-control" required>
                        <option value="">Select category</option>
                        <option value="booking">Booking Issues</option>
                        <option value="payment">Payment Problems</option>
                        <option value="technical">Technical Support</option>
                        <option value="account">Account Management</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Priority</label>
                    <select class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reservation ID (if applicable)</label>
                    <input type="text" class="form-control" placeholder="e.g., SP123456">
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" rows="5" placeholder="Please provide detailed information about your issue..." required></textarea>
            </div>
            
            <div class="form-group">
                <label>Attachments</label>
                <div class="file-upload">
                    <input type="file" id="attachments" multiple accept="image/*,.pdf,.doc,.docx">
                    <label for="attachments" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Click to upload files or drag and drop</span>
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline">Save as Draft</button>
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- System Status -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-server"></i> System Status</h3>
    </div>
    <div class="card-body">
        <div class="status-list">
            <div class="status-item">
                <div class="status-indicator operational"></div>
                <div class="status-info">
                    <h4>Booking System</h4>
                    <p>All systems operational</p>
                </div>
                <div class="status-time">
                    <small>Last updated: 2 minutes ago</small>
                </div>
            </div>
            
            <div class="status-item">
                <div class="status-indicator operational"></div>
                <div class="status-info">
                    <h4>Payment Processing</h4>
                    <p>All systems operational</p>
                </div>
                <div class="status-time">
                    <small>Last updated: 1 minute ago</small>
                </div>
            </div>
            
            <div class="status-item">
                <div class="status-indicator maintenance"></div>
                <div class="status-info">
                    <h4>Mobile App</h4>
                    <p>Scheduled maintenance: Jan 20, 2:00 AM - 4:00 AM</p>
                </div>
                <div class="status-time">
                    <small>Last updated: 5 minutes ago</small>
                </div>
            </div>
            
            <div class="status-item">
                <div class="status-indicator operational"></div>
                <div class="status-info">
                    <h4>Notifications</h4>
                    <p>All systems operational</p>
                </div>
                <div class="status-time">
                    <small>Last updated: 3 minutes ago</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>