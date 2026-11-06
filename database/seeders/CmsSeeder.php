<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * The legal pages are required before the app stores will accept a
 * submission, and Razorpay will not activate an account without a visible
 * refund policy — so they ship as seeded content rather than as a launch-week
 * scramble.
 *
 * The copy here is a working draft for legal review, not final wording.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->pages();
        $this->faqs();
    }

    protected function pages(): void
    {
        $pages = [
            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'meta_title' => 'About Us — Matrimony',
                'meta_description' => 'Who we are and how we approach matchmaking.',
                'content' => <<<'HTML'
                    <h2>About Matrimony</h2>
                    <p>We help people find a life partner who fits their values, family and
                    circumstances. Every profile is reviewed before it goes live, contact
                    details are never shown without consent, and matches are ranked by
                    stated preferences rather than by who paid the most.</p>
                    <h3>How we are different</h3>
                    <ul>
                      <li><strong>Verified profiles.</strong> Government ID verification is
                      offered to every member and displayed as a badge once approved.</li>
                      <li><strong>Real privacy controls.</strong> You decide who sees your
                      photos, your contact details and whether you appear in search at all.</li>
                      <li><strong>Honest matching.</strong> Recommendations are scored in
                      both directions, so you are shown people for whom you are also a
                      good match.</li>
                    </ul>
                    HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy — Matrimony',
                'meta_description' => 'What we collect, why we collect it and how you stay in control.',
                'content' => <<<'HTML'
                    <h2>Privacy Policy</h2>
                    <p>This policy explains what we collect, why, and the control you have.</p>
                    <h3>What we collect</h3>
                    <p>Account details (name, mobile, email, date of birth), the profile
                    information you choose to enter, photos you upload, verification
                    documents if you submit them, and technical data such as IP address
                    and device type.</p>
                    <h3>How we use it</h3>
                    <p>To create and rank matches, to operate the service, to prevent fraud
                    and abuse, and to meet legal obligations. We do not sell personal data.</p>
                    <h3>Who can see your information</h3>
                    <p>Your privacy settings govern every read. Contact details are shown
                    only to members whose plan permits it and only where you have allowed
                    that audience. Verification documents are never shown to other members
                    under any circumstances, and staff access to them is logged.</p>
                    <h3>Retention</h3>
                    <p>Deleting your account hides it immediately and permanently erases
                    your data after 30 days. Financial records are retained as long as tax
                    law requires.</p>
                    <h3>Your rights</h3>
                    <p>You can export your data, correct it, restrict its visibility or
                    delete it from your account settings at any time.</p>
                    HTML,
            ],
            [
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'meta_title' => 'Terms & Conditions — Matrimony',
                'meta_description' => 'The rules for using this service.',
                'content' => <<<'HTML'
                    <h2>Terms &amp; Conditions</h2>
                    <h3>Eligibility</h3>
                    <p>You must be of legal marriageable age in India — 21 for men, 18 for
                    women — and legally free to marry. Accounts that misstate marital
                    status are removed.</p>
                    <h3>Your conduct</h3>
                    <p>Provide accurate information. Do not impersonate anyone, solicit
                    money, harass other members or share another member's details outside
                    the platform. These are grounds for immediate suspension.</p>
                    <h3>Our role</h3>
                    <p>We provide a platform for introductions. We verify what we
                    reasonably can, but we do not guarantee the accuracy of any member's
                    claims. Meet safely, verify independently, and never send money to
                    anyone you have met here.</p>
                    <h3>Memberships</h3>
                    <p>Paid plans grant the features listed at the time of purchase for the
                    stated duration. Changes to plan pricing or features do not affect a
                    membership already purchased.</p>
                    HTML,
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund & Cancellation Policy',
                'meta_title' => 'Refund Policy — Matrimony',
                'meta_description' => 'When a membership payment can be refunded.',
                'content' => <<<'HTML'
                    <h2>Refund &amp; Cancellation Policy</h2>
                    <p>Memberships are digital services activated immediately on payment.</p>
                    <h3>Eligible for refund</h3>
                    <ul>
                      <li>A duplicate charge for the same order.</li>
                      <li>A payment debited where the membership was not activated.</li>
                      <li>A request made within 7 days of purchase where no paid feature —
                      contact views, chat or interests beyond the free allowance — has been
                      used.</li>
                    </ul>
                    <h3>Not eligible</h3>
                    <ul>
                      <li>Requests made after the 7-day window.</li>
                      <li>Memberships where paid features have already been consumed.</li>
                      <li>Accounts suspended for violating these terms.</li>
                    </ul>
                    <h3>How to request</h3>
                    <p>Write to support with your registered mobile number and the payment
                    reference. Approved refunds are returned to the original payment method
                    within 5–7 working days.</p>
                    HTML,
            ],
            [
                'slug' => 'contact-us',
                'title' => 'Contact Us',
                'meta_title' => 'Contact Us — Matrimony',
                'meta_description' => 'How to reach our support team.',
                'content' => <<<'HTML'
                    <h2>Contact Us</h2>
                    <p>Support is available Monday to Saturday, 9:00 to 18:00 IST.</p>
                    <p>Email: support@matrimony.test<br>Phone: +91 90000 00000</p>
                    <p>For anything involving safety, harassment or a suspected fake
                    profile, use the Report button on the profile itself — those reach the
                    moderation queue directly and are actioned ahead of general support.</p>
                    HTML,
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page + ['is_published' => true, 'published_at' => now()],
            );
        }
    }

    protected function faqs(): void
    {
        $faqs = [
            ['getting_started', 'How do I create a profile?', 'Register with your mobile number, verify the OTP, then complete the profile sections. A profile at 80% or more completion appears far higher in search results.'],
            ['getting_started', 'Why is my profile pending approval?', 'Every new profile is reviewed by our moderation team before it becomes visible. This usually takes a few hours and protects everyone from fake accounts.'],
            ['getting_started', 'Can my parents create a profile for me?', 'Yes. Choose who is creating the profile during registration — parent, sibling, relative or friend — and the wording throughout adapts.'],
            ['privacy', 'Who can see my photos?', 'You choose: everyone, registered members, premium members only, or only people you have connected with. Anyone outside that audience sees a blurred version.'],
            ['privacy', 'Who can see my mobile number?', 'Only members whose plan includes contact access and who fall within the audience you selected in privacy settings. You can restrict it to connections only.'],
            ['privacy', 'Can I hide my profile temporarily?', 'Yes. Deactivate from your profile settings — your data is kept and you can restore it simply by logging in again.'],
            ['matching', 'How are matches calculated?', 'We score compatibility across religion, community, age, language, location, education, occupation, lifestyle, income and height, weighted by what you said matters. Scores are calculated in both directions, so you see people for whom you are also a good match.'],
            ['matching', 'Why do I see profiles outside my preferences?', 'Preferences are ranking signals rather than hard filters, so a strong match that misses one criterion is not hidden from you. Use search filters when you want strict boundaries.'],
            ['interests', 'What happens when I send an interest?', 'The other member is notified and can accept or decline. If they accept, you become a connection and can start chatting.'],
            ['interests', 'Can I resend a declined interest?', 'Not immediately. There is a 30-day cooling-off period before an interest can be sent to the same profile again.'],
            ['membership', 'What do paid plans include?', 'More interests, contact access, chat, seeing who viewed your profile and priority in match results. The plans page lists exactly what each tier grants.'],
            ['membership', 'What happens to my plan if prices change?', 'Nothing. Your membership keeps the features it had when you bought it, for its full duration.'],
            ['membership', 'How do I get an invoice?', 'Every successful payment generates a GST invoice, downloadable from Payment History in your account.'],
            ['safety', 'How do I report someone?', 'Use the Report button on their profile or in the chat. Reports go straight to our moderation team and are reviewed ahead of general support tickets.'],
            ['safety', 'Someone is asking me for money. What do I do?', 'Do not send anything. Report the profile immediately using the "Fraud or money request" category. This is the most common scam on matrimonial platforms and we act on it quickly.'],
            ['safety', 'What does the verified badge mean?', 'That member submitted a government ID and our team confirmed it matches their profile name. It does not verify anything else they have claimed.'],
            ['account', 'How do I delete my account?', 'Profile settings → Delete account. Your profile disappears immediately and all personal data is permanently erased after 30 days.'],
            ['account', 'I did not receive my OTP.', 'Wait for the resend timer, then request a new code. Check that the number is correct and that your phone has signal. If it still fails, contact support.'],
        ];

        foreach ($faqs as $index => [$category, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                ['category' => $category, 'answer' => $answer, 'sort_order' => $index, 'is_published' => true],
            );
        }
    }
}
