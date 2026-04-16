<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Fetch active plans and time slots
$plans = getActivePlans();
$timeSlots = getActiveTimeSlots();

// Fetch working equipment
$db = getDB();
$equipment = $db->query("SELECT * FROM equipment WHERE status = 'working' ORDER BY created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Transform Your Body, Transform Your Life</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Join FitZone Gym - Premier fitness center with modern equipment, expert trainers, and flexible membership plans.">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="logo">
                <span>💪</span> FitZone
            </a>
            <ul class="nav-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#plans">Plans</a></li>
                <li><a href="#equipment">Equipment</a></li>
                <li><a href="#slots">Time Slots</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="user/login.php" class="btn btn-primary btn-sm">Login</a></li>
                <li><a href="user/register.php" class="btn btn-secondary btn-sm">Register</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Transform Your <span class="highlight">Body</span></h1>
            <p>Build strength, gain confidence, and achieve your fitness goals with expert guidance</p>
            <div class="hero-buttons">
                <a href="user/register.php" class="btn btn-primary">Join Now</a>
                <a href="#plans" class="btn btn-secondary">View Plans</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>About FitZone Gym</h2>
                <p>Your Journey to Fitness Starts Here</p>
            </div>
            <div class="about-content">
                <div class="about-image animate-on-scroll">
                    <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600" alt="Gym Interior">
                </div>
                <div class="about-text animate-on-scroll">
                    <h3>Why Choose FitZone?</h3>
                    <p>At FitZone Gym, we believe that fitness is not just about physical transformation—it's about building a healthier, more confident you. Our state-of-the-art facility is equipped with the latest fitness equipment and staffed by certified trainers who are dedicated to helping you achieve your goals.</p>
                    <p>Whether you're a beginner taking your first steps toward fitness or an experienced athlete looking to push your limits, we provide a supportive and motivating environment for everyone.</p>
                    <ul class="feature-list">
                        <li>Modern Equipment & Facilities</li>
                        <li>Certified Personal Trainers</li>
                        <li>Flexible Membership Plans</li>
                        <li>Group Fitness Classes</li>
                        <li>Nutrition Guidance</li>
                        <li>Clean & Safe Environment</li>
                    </ul>
                    <a href="user/register.php" class="btn btn-primary">Start Your Journey</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Plans Section -->
    <section id="plans" class="plans">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Membership Plans</h2>
                <p>Choose the plan that fits your lifestyle</p>
            </div>
            <div class="plans-grid">
                <?php if (!empty($plans)): ?>
                    <?php foreach ($plans as $index => $plan): ?>
                        <div class="plan-card animate-on-scroll <?php echo $index === 2 ? 'featured' : ''; ?>">
                            <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="plan-price">₹<?php echo number_format($plan['price'], 0); ?></div>
                            <div class="plan-duration"><?php echo $plan['duration_days']; ?> Days</div>
                            <p><?php echo htmlspecialchars($plan['description']); ?></p>
                            <ul class="plan-features">
                                <li>✓ Access to all equipment</li>
                                <li>✓ Locker facility</li>
                                <li>✓ Free fitness assessment</li>
                                <?php if ($index >= 1): ?>
                                    <li>✓ Group classes included</li>
                                    <li>✓ Diet consultation</li>
                                <?php endif; ?>
                                <?php if ($index >= 2): ?>
                                    <li>✓ Personal training sessions</li>
                                    <li>✓ Priority slot booking</li>
                                <?php endif; ?>
                                <?php if ($index === 3): ?>
                                    <li>✓ Spa & sauna access</li>
                                    <li>✓ Advanced training programs</li>
                                <?php endif; ?>
                            </ul>
                            <a href="user/register.php" class="btn <?php echo $index === 2 ? 'btn-secondary' : 'btn-primary'; ?>">Choose Plan</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No plans available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <!-- Equipment Section -->
    <section id="equipment" class="equipment-section">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Our Equipment</h2>
                <p>State-of-the-art fitness equipment for your workout</p>
            </div>
            <div class="equipment-grid">
                <?php if (!empty($equipment)): ?>
                    <?php foreach ($equipment as $item): ?>
                        <div class="equipment-card animate-on-scroll">
                            <?php if ($item['equipment_image'] && file_exists('assets/images/equipment/' . $item['equipment_image'])): ?>
                                <div class="equipment-image">
                                    <img src="assets/images/equipment/<?php echo htmlspecialchars($item['equipment_image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="equipment-image equipment-placeholder">
                                    <span style="font-size: 4rem;">🏋️</span>
                                </div>
                            <?php endif; ?>
                            <div class="equipment-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <?php if ($item['notes']): ?>
                                    <p><?php echo htmlspecialchars($item['notes']); ?></p>
                                <?php endif; ?>
                                <span class="equipment-status">
                                    <span style="color: #28a745; font-weight: 600;">✓ Available</span>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1 / -1; padding: 2rem; color: #666;">Equipment information coming soon.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Time Slots Section -->
    <section id="slots" class="time-slots">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Available Time Slots</h2>
                <p>Choose a time that works best for you</p>
            </div>
            <div class="slots-grid">
                <?php if (!empty($timeSlots)): ?>
                    <?php foreach ($timeSlots as $slot): ?>
                        <div class="slot-card animate-on-scroll">
                            <h4><?php echo htmlspecialchars($slot['slot_name']); ?></h4>
                            <div class="slot-time">
                                <?php echo date('g:i A', strtotime($slot['start_time'])); ?> -
                                <?php echo date('g:i A', strtotime($slot['end_time'])); ?>
                            </div>
                            <div class="slot-capacity">
                                <?php
                                $available = $slot['max_members'] - $slot['current_members'];
                                echo "Available: {$available} / {$slot['max_members']}";
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No time slots available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>


    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-title animate-on-scroll">
                <h2>Get In Touch</h2>
                <p>We'd love to hear from you</p>
            </div>
            <div class="contact-content">
                <div class="contact-form animate-on-scroll">
                    <h3>Send Us a Message</h3>
                    <form id="contactForm" method="POST" action="contact_handler.php">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
                <div class="contact-info animate-on-scroll">
                    <h3>Contact Information</h3>
                    <div class="info-item">
                        <i>📍</i>
                        <div>
                            <h4>Address</h4>
                            <p>P&M Mall, Kurji<br>Patna, Bihar 800010</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i>📞</i>
                        <div>
                            <h4>Phone</h4>
                            <p>+91 98765 43210<br>+91 87654 32109</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i>📧</i>
                        <div>
                            <h4>Email</h4>
                            <p>info@fitzonegym.com<br>support@fitzonegym.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i>⏰</i>
                        <div>
                            <h4>Opening Hours</h4>
                            <p>Monday - Saturday: 6:00 AM - 10:00 PM<br>Sunday: 7:00 AM - 8:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            <p><a href="https://www.instagram.com/its.vivek.raj/" target="_blank">Designed</a> with ❤️ for fitness enthusiasts | <a href="admin/login.php">Admin Login</a></p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>

</html>