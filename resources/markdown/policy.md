# Tuklas Privacy Notice

Updated 5 November 2026 · Applies to youth, trainers, administrators, and visitors using the Tuklas website and mobile API.

**Project notice — operator details and retention schedule pending.** Tuklas is operated by the Tour de Force project team, Universidad de Dagupan. The team must publish its legal operator name, public privacy contact, and approved retention schedule before collecting real users' data in production. This notice describes the current implementation; it is not a certification of legal compliance.

## What Tuklas collects and why

- **Accounts:** name, email, password hash, role, active status, verification status, and account timestamps support registration, sign-in, administration, and account recovery. Where supplied, first and last names and birth date are stored separately.
- **Youth profiles:** barangay, contact number, education, employment status, livelihood interests, skills, credentials, interests, and assessment preferences support career and training guidance. Profile completion for ages 15–17 requests guardian name, relationship, and contact number. Tell your guardian before providing their details. Recording these fields does not verify guardian consent.
- **Trainer records:** qualifications, teaching assignments, and authored training program information support training management. Published catalog entries can show trainer names.
- **Documents and AI:** uploaded resumes or certificates, filenames, file type and size, analysis results, detected skills, credentials, recommendations, and processing dates support scanning. Skills and credentials from a scan may be added to your youth profile.
- **Chat:** submitted messages, AI replies, source links, and timestamps are saved to your account. Recent messages and selected career context are used to continue conversations.
- **Activity and security:** notifications record actions such as registration, profile updates, scans, chat use, and account administration. Session storage can include IP address, browser information, and activity times. Mobile token records include device names and expiry/use times. Two-factor setup and recovery information are stored when enabled. Technical errors may be recorded in server logs.

## Who can see information

Youth can access their own workspace information. Trainers and administrators can view active youth accounts and training selections through the training-choice workflow. Administrators can manage user accounts and youth profile details, view youth scan recommendations through PESO, and receive named activity notices from all roles. Chat-history endpoints are scoped to the signed-in account; activity notices disclose that chat was used, not the conversation text. Authorized server/database operators may access stored records for administration and support.

Staff must use access only for assigned Tuklas duties, keep records confidential, and avoid copying or sharing personal information through unrelated channels. Role restrictions do not make stored data anonymous.

## External services and AI processing

When you use scanning, Tuklas sends the document contents, your supplied goal, relevant preferences, and training context to Google's Gemini service. Chat sends recent conversation messages and selected preferences and scan-derived career information. Search and URL-context tools may be used when enabled. These are external processing operations and may involve processing outside the Philippines. Provider handling depends on the configured service and account terms; Tuklas cannot promise that provider copies are immediately deleted or never used by the provider. See [Google's Gemini API terms](https://ai.google.dev/gemini-api/terms) before submitting sensitive content.

Email and password are used to authenticate your account. Configured hosting and mail services process data needed to run the application and deliver verification or recovery messages. External TESDA and job links have their own privacy practices.

Do not submit passwords, financial account details, government identity numbers, medical records, or unnecessary information about other people in documents or chat. You can choose not to use AI features. This notice is information about processing, not blanket permission for unrelated uses, advertising, or selling data.

## Cookies and device storage

The website uses session, security, and remember-me cookies for account functions and local storage for the chosen theme. The mobile app uses secure device storage for its authentication token and local preferences for appearance/onboarding. Sign out on shared devices. Clearing local data does not erase server records.

## Storage, retention, and deletion

The scanner temporarily stores uploads on the server and attempts to delete the temporary file after processing, including handled failures. It retains analysis results and document metadata; an interrupted process may leave temporary files requiring cleanup. Removing a temporary upload does not remove information already sent to an AI provider.

**Deleting an account currently soft-deletes it:** access is removed and tokens are revoked, but the database record and related profile, scan, and chat records are not automatically permanently erased. Administrators can restore deleted accounts. Copies of named activity notices can remain in administrators' notifications. Backups, logs, and external provider records are separate from account deletion.

No automatic retention schedule for these records is implemented. Ask the project team for a review of retention, restriction, or permanent erasure rather than assuming the Delete account button erases everything. The team must establish retention periods and deletion procedures before production use; no fixed erasure deadline is promised here.

## Your choices and rights

You can update account and youth-profile information through available screens, use account security settings, sign out, and request account deletion on the website. For copies, corrections outside those screens, objections, withdrawal of consent where consent is the basis, or restriction/erasure requests, contact the Tour de Force project team through the person who provided your access. A dedicated public privacy contact has not yet been supplied. Do not send passwords or recovery codes with a request.

Under Philippine privacy law, applicable rights include being informed, access, correction, objection, erasure/blocking, portability, and complaint or redress, subject to legal conditions. See the [National Privacy Commission's data-subject rights guidance](https://privacy.gov.ph/data-subject-rights/). Account closure does not waive these rights. The team must identify and document the applicable lawful basis for each processing purpose; using Tuklas does not itself supply every required consent.

## Security and changes

Tuklas uses password hashing, role checks, protected routes, authentication token controls, and rate limits on selected sensitive actions. No online system can guarantee absolute security. Report suspected unauthorized access to the project team promptly. Changes to data uses, providers, or retention should be reflected in an updated notice and communicated before materially different processing; additional consent must be obtained where required.
