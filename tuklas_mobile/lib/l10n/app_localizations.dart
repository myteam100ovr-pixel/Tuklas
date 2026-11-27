import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_en.dart';
import 'app_localizations_tl.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('en'),
    Locale('tl'),
  ];

  /// No description provided for @languageName.
  ///
  /// In en, this message translates to:
  /// **'English'**
  String get languageName;

  /// No description provided for @languageSettings.
  ///
  /// In en, this message translates to:
  /// **'Language / Wika'**
  String get languageSettings;

  /// No description provided for @languageSettingsDescription.
  ///
  /// In en, this message translates to:
  /// **'Choose the language used in Tuklas.'**
  String get languageSettingsDescription;

  /// No description provided for @english.
  ///
  /// In en, this message translates to:
  /// **'English'**
  String get english;

  /// No description provided for @tagalogFilipino.
  ///
  /// In en, this message translates to:
  /// **'Tagalog (Filipino)'**
  String get tagalogFilipino;

  /// No description provided for @saveLanguageError.
  ///
  /// In en, this message translates to:
  /// **'Your language was saved on this device, but could not be updated on your account. Try again when you are online.'**
  String get saveLanguageError;

  /// No description provided for @languageSaved.
  ///
  /// In en, this message translates to:
  /// **'Language updated.'**
  String get languageSaved;

  /// No description provided for @home.
  ///
  /// In en, this message translates to:
  /// **'Home'**
  String get home;

  /// No description provided for @jobs.
  ///
  /// In en, this message translates to:
  /// **'Jobs'**
  String get jobs;

  /// No description provided for @training.
  ///
  /// In en, this message translates to:
  /// **'Training'**
  String get training;

  /// No description provided for @inbox.
  ///
  /// In en, this message translates to:
  /// **'Inbox'**
  String get inbox;

  /// No description provided for @scanner.
  ///
  /// In en, this message translates to:
  /// **'Scanner'**
  String get scanner;

  /// No description provided for @more.
  ///
  /// In en, this message translates to:
  /// **'More'**
  String get more;

  /// No description provided for @profile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get profile;

  /// No description provided for @applications.
  ///
  /// In en, this message translates to:
  /// **'Applications'**
  String get applications;

  /// No description provided for @programs.
  ///
  /// In en, this message translates to:
  /// **'Programs'**
  String get programs;

  /// No description provided for @users.
  ///
  /// In en, this message translates to:
  /// **'Users'**
  String get users;

  /// No description provided for @courses.
  ///
  /// In en, this message translates to:
  /// **'Courses'**
  String get courses;

  /// No description provided for @courseCatalog.
  ///
  /// In en, this message translates to:
  /// **'Course catalog'**
  String get courseCatalog;

  /// No description provided for @scholarships.
  ///
  /// In en, this message translates to:
  /// **'Scholarships'**
  String get scholarships;

  /// No description provided for @careerPaths.
  ///
  /// In en, this message translates to:
  /// **'Career paths'**
  String get careerPaths;

  /// No description provided for @centers.
  ///
  /// In en, this message translates to:
  /// **'Centers'**
  String get centers;

  /// No description provided for @trainers.
  ///
  /// In en, this message translates to:
  /// **'Trainers'**
  String get trainers;

  /// No description provided for @dates.
  ///
  /// In en, this message translates to:
  /// **'Dates'**
  String get dates;

  /// No description provided for @requests.
  ///
  /// In en, this message translates to:
  /// **'Requests'**
  String get requests;

  /// No description provided for @results.
  ///
  /// In en, this message translates to:
  /// **'Results'**
  String get results;

  /// No description provided for @messages.
  ///
  /// In en, this message translates to:
  /// **'Messages'**
  String get messages;

  /// No description provided for @notifications.
  ///
  /// In en, this message translates to:
  /// **'Notifications'**
  String get notifications;

  /// No description provided for @tuklasBot.
  ///
  /// In en, this message translates to:
  /// **'TuklasBot'**
  String get tuklasBot;

  /// No description provided for @pesoAi.
  ///
  /// In en, this message translates to:
  /// **'PESO AI'**
  String get pesoAi;

  /// No description provided for @accountAndSecurity.
  ///
  /// In en, this message translates to:
  /// **'Account & security'**
  String get accountAndSecurity;

  /// No description provided for @patchNotes.
  ///
  /// In en, this message translates to:
  /// **'Patch Notes'**
  String get patchNotes;

  /// No description provided for @myRequests.
  ///
  /// In en, this message translates to:
  /// **'My requests'**
  String get myRequests;

  /// No description provided for @myResults.
  ///
  /// In en, this message translates to:
  /// **'My results'**
  String get myResults;

  /// No description provided for @myCourses.
  ///
  /// In en, this message translates to:
  /// **'My courses'**
  String get myCourses;

  /// No description provided for @trainingPlans.
  ///
  /// In en, this message translates to:
  /// **'Training plans'**
  String get trainingPlans;

  /// No description provided for @skillsAssessment.
  ///
  /// In en, this message translates to:
  /// **'Skills assessment'**
  String get skillsAssessment;

  /// No description provided for @assignTrainers.
  ///
  /// In en, this message translates to:
  /// **'Assign trainers'**
  String get assignTrainers;

  /// No description provided for @openings.
  ///
  /// In en, this message translates to:
  /// **'Openings'**
  String get openings;

  /// No description provided for @moreTools.
  ///
  /// In en, this message translates to:
  /// **'More tools'**
  String get moreTools;

  /// No description provided for @profileAndSettings.
  ///
  /// In en, this message translates to:
  /// **'Profile and settings'**
  String get profileAndSettings;

  /// No description provided for @workspace.
  ///
  /// In en, this message translates to:
  /// **'Workspace'**
  String get workspace;

  /// No description provided for @workspaceLabel.
  ///
  /// In en, this message translates to:
  /// **'WORKSPACE'**
  String get workspaceLabel;

  /// No description provided for @lightDarkMode.
  ///
  /// In en, this message translates to:
  /// **'Light / dark mode'**
  String get lightDarkMode;

  /// No description provided for @pageOf.
  ///
  /// In en, this message translates to:
  /// **'Page {current} of {total}'**
  String pageOf(int current, int total);

  /// No description provided for @previous.
  ///
  /// In en, this message translates to:
  /// **'Previous'**
  String get previous;

  /// No description provided for @next.
  ///
  /// In en, this message translates to:
  /// **'Next'**
  String get next;

  /// No description provided for @cancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get cancel;

  /// No description provided for @save.
  ///
  /// In en, this message translates to:
  /// **'Save'**
  String get save;

  /// No description provided for @saving.
  ///
  /// In en, this message translates to:
  /// **'Saving…'**
  String get saving;

  /// No description provided for @done.
  ///
  /// In en, this message translates to:
  /// **'Done'**
  String get done;

  /// No description provided for @confirm.
  ///
  /// In en, this message translates to:
  /// **'Confirm'**
  String get confirm;

  /// No description provided for @tryAgain.
  ///
  /// In en, this message translates to:
  /// **'Try again'**
  String get tryAgain;

  /// No description provided for @retryConnection.
  ///
  /// In en, this message translates to:
  /// **'Retry connection'**
  String get retryConnection;

  /// No description provided for @logIn.
  ///
  /// In en, this message translates to:
  /// **'Log in'**
  String get logIn;

  /// No description provided for @logOut.
  ///
  /// In en, this message translates to:
  /// **'Log out'**
  String get logOut;

  /// No description provided for @signOut.
  ///
  /// In en, this message translates to:
  /// **'Sign out'**
  String get signOut;

  /// No description provided for @email.
  ///
  /// In en, this message translates to:
  /// **'Email'**
  String get email;

  /// No description provided for @password.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get password;

  /// No description provided for @firstName.
  ///
  /// In en, this message translates to:
  /// **'First name'**
  String get firstName;

  /// No description provided for @lastName.
  ///
  /// In en, this message translates to:
  /// **'Last name'**
  String get lastName;

  /// No description provided for @middleInitialOptional.
  ///
  /// In en, this message translates to:
  /// **'Middle initial (optional)'**
  String get middleInitialOptional;

  /// No description provided for @contactNumber.
  ///
  /// In en, this message translates to:
  /// **'Contact number'**
  String get contactNumber;

  /// No description provided for @municipality.
  ///
  /// In en, this message translates to:
  /// **'Municipality'**
  String get municipality;

  /// No description provided for @barangay.
  ///
  /// In en, this message translates to:
  /// **'Barangay'**
  String get barangay;

  /// No description provided for @dateOfBirth.
  ///
  /// In en, this message translates to:
  /// **'Date of birth'**
  String get dateOfBirth;

  /// No description provided for @confirmPassword.
  ///
  /// In en, this message translates to:
  /// **'Confirm password'**
  String get confirmPassword;

  /// No description provided for @forgotPassword.
  ///
  /// In en, this message translates to:
  /// **'Forgot password?'**
  String get forgotPassword;

  /// No description provided for @requestPasswordReset.
  ///
  /// In en, this message translates to:
  /// **'Request password reset'**
  String get requestPasswordReset;

  /// No description provided for @newToTuklas.
  ///
  /// In en, this message translates to:
  /// **'New to Tuklas? Create an account'**
  String get newToTuklas;

  /// No description provided for @alreadyHaveAccount.
  ///
  /// In en, this message translates to:
  /// **'Already have an account? Log in'**
  String get alreadyHaveAccount;

  /// No description provided for @verifyYourEmail.
  ///
  /// In en, this message translates to:
  /// **'Verify your email'**
  String get verifyYourEmail;

  /// No description provided for @verificationInstructions.
  ///
  /// In en, this message translates to:
  /// **'Open the verification link in your email to access your workspace.'**
  String get verificationInstructions;

  /// No description provided for @verifiedEmailButton.
  ///
  /// In en, this message translates to:
  /// **'I have verified my email'**
  String get verifiedEmailButton;

  /// No description provided for @resendVerificationEmail.
  ///
  /// In en, this message translates to:
  /// **'Resend verification email'**
  String get resendVerificationEmail;

  /// No description provided for @pleaseWait.
  ///
  /// In en, this message translates to:
  /// **'Please wait…'**
  String get pleaseWait;

  /// No description provided for @welcomeEyebrow.
  ///
  /// In en, this message translates to:
  /// **'WELCOME TO YOUR NEXT STEP'**
  String get welcomeEyebrow;

  /// No description provided for @whatToExpect.
  ///
  /// In en, this message translates to:
  /// **'What to expect'**
  String get whatToExpect;

  /// No description provided for @welcomeDescription.
  ///
  /// In en, this message translates to:
  /// **'Discover your skills. Explore possibilities. Build a path that feels like you.'**
  String get welcomeDescription;

  /// No description provided for @welcomeFeatureOneTitle.
  ///
  /// In en, this message translates to:
  /// **'Discover what you bring'**
  String get welcomeFeatureOneTitle;

  /// No description provided for @welcomeFeatureOneDescription.
  ///
  /// In en, this message translates to:
  /// **'Upload a resume or certificate. The AI scanner helps identify your skills and possible next steps.'**
  String get welcomeFeatureOneDescription;

  /// No description provided for @welcomeFeatureTwoTitle.
  ///
  /// In en, this message translates to:
  /// **'Explore careers and TESDA'**
  String get welcomeFeatureTwoTitle;

  /// No description provided for @welcomeFeatureTwoDescription.
  ///
  /// In en, this message translates to:
  /// **'See scan-based career and training recommendations. These are guidance, not confirmed jobs or guaranteed training slots.'**
  String get welcomeFeatureTwoDescription;

  /// No description provided for @welcomeFeatureThreeTitle.
  ///
  /// In en, this message translates to:
  /// **'Make it yours'**
  String get welcomeFeatureThreeTitle;

  /// No description provided for @welcomeFeatureThreeDescription.
  ///
  /// In en, this message translates to:
  /// **'Sign in or create an account, complete your profile, and keep your scans and saved details connected across mobile and web.'**
  String get welcomeFeatureThreeDescription;

  /// No description provided for @welcomePrivacyNote.
  ///
  /// In en, this message translates to:
  /// **'AI features require a connection. Documents you choose to scan are sent for AI analysis; review suggestions before acting on them.'**
  String get welcomePrivacyNote;

  /// No description provided for @gettingReady.
  ///
  /// In en, this message translates to:
  /// **'Getting ready…'**
  String get gettingReady;

  /// No description provided for @getStarted.
  ///
  /// In en, this message translates to:
  /// **'Get started'**
  String get getStarted;

  /// No description provided for @passwordStrength.
  ///
  /// In en, this message translates to:
  /// **'Password strength'**
  String get passwordStrength;

  /// No description provided for @weak.
  ///
  /// In en, this message translates to:
  /// **'Weak'**
  String get weak;

  /// No description provided for @okay.
  ///
  /// In en, this message translates to:
  /// **'Okay'**
  String get okay;

  /// No description provided for @strong.
  ///
  /// In en, this message translates to:
  /// **'Strong'**
  String get strong;

  /// No description provided for @passwordRequirements.
  ///
  /// In en, this message translates to:
  /// **'Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.'**
  String get passwordRequirements;

  /// No description provided for @passwordMeetsRequirements.
  ///
  /// In en, this message translates to:
  /// **'Okay. It meets the password requirements.'**
  String get passwordMeetsRequirements;

  /// No description provided for @passwordStrongExtraLength.
  ///
  /// In en, this message translates to:
  /// **'Strong. This password meets the requirements with extra length.'**
  String get passwordStrongExtraLength;

  /// No description provided for @passwordsMatch.
  ///
  /// In en, this message translates to:
  /// **'Passwords match.'**
  String get passwordsMatch;

  /// No description provided for @passwordsDoNotMatch.
  ///
  /// In en, this message translates to:
  /// **'Passwords do not match.'**
  String get passwordsDoNotMatch;

  /// No description provided for @termsOfUse.
  ///
  /// In en, this message translates to:
  /// **'Terms of use'**
  String get termsOfUse;

  /// No description provided for @privacyNotice.
  ///
  /// In en, this message translates to:
  /// **'Privacy notice'**
  String get privacyNotice;

  /// No description provided for @agreeToTerms.
  ///
  /// In en, this message translates to:
  /// **'I agree to the Terms of use and acknowledge the Privacy notice.'**
  String get agreeToTerms;

  /// No description provided for @showPassword.
  ///
  /// In en, this message translates to:
  /// **'Show password'**
  String get showPassword;

  /// No description provided for @hidePassword.
  ///
  /// In en, this message translates to:
  /// **'Hide password'**
  String get hidePassword;

  /// No description provided for @authenticatorOrRecoveryCode.
  ///
  /// In en, this message translates to:
  /// **'Authenticator or recovery code'**
  String get authenticatorOrRecoveryCode;

  /// No description provided for @acceptTermsError.
  ///
  /// In en, this message translates to:
  /// **'Please accept the Terms of use and Privacy notice.'**
  String get acceptTermsError;

  /// No description provided for @accountCreatedVerificationNotice.
  ///
  /// In en, this message translates to:
  /// **'Account created. Verify your email address before signing in.'**
  String get accountCreatedVerificationNotice;

  /// No description provided for @chooseMunicipalityBarangayError.
  ///
  /// In en, this message translates to:
  /// **'Choose your municipality and barangay.'**
  String get chooseMunicipalityBarangayError;

  /// No description provided for @specialCharacterExample.
  ///
  /// In en, this message translates to:
  /// **'Use 1 special character, e.g. @'**
  String get specialCharacterExample;

  /// No description provided for @passwordLengthError.
  ///
  /// In en, this message translates to:
  /// **'Enter at least {length} characters.'**
  String passwordLengthError(int length);

  /// No description provided for @chooseSkillCount.
  ///
  /// In en, this message translates to:
  /// **'Choose {min} to {max} skills.'**
  String chooseSkillCount(int min, int max);

  /// No description provided for @pathSkillsGuidance.
  ///
  /// In en, this message translates to:
  /// **'Select up to 8 skills from the path source. A published path needs at least 3.'**
  String get pathSkillsGuidance;

  /// No description provided for @couldNotLoadConversation.
  ///
  /// In en, this message translates to:
  /// **'Could not load conversation history. Try again.'**
  String get couldNotLoadConversation;

  /// No description provided for @couldNotReachCareerAssistant.
  ///
  /// In en, this message translates to:
  /// **'Could not reach the career assistant. Check your connection and try again.'**
  String get couldNotReachCareerAssistant;

  /// No description provided for @chatHowCanIHelp.
  ///
  /// In en, this message translates to:
  /// **'How can I help you?'**
  String get chatHowCanIHelp;

  /// No description provided for @chatIntro.
  ///
  /// In en, this message translates to:
  /// **'Ask Tuklas about careers, AI skills, TESDA training, or a practical next step.'**
  String get chatIntro;

  /// No description provided for @exploreCareers.
  ///
  /// In en, this message translates to:
  /// **'Explore careers'**
  String get exploreCareers;

  /// No description provided for @aiSkillsTesda.
  ///
  /// In en, this message translates to:
  /// **'AI skills + TESDA'**
  String get aiSkillsTesda;

  /// No description provided for @planNextStep.
  ///
  /// In en, this message translates to:
  /// **'Plan a next step'**
  String get planNextStep;

  /// No description provided for @buildMySkills.
  ///
  /// In en, this message translates to:
  /// **'Build my skills'**
  String get buildMySkills;

  /// No description provided for @promptExploreCareers.
  ///
  /// In en, this message translates to:
  /// **'Help me explore careers that match my interests.'**
  String get promptExploreCareers;

  /// No description provided for @promptAiSkillsTesda.
  ///
  /// In en, this message translates to:
  /// **'I want to work in AI. What skills should I develop, and which TESDA NC or online TESDA course should I consider first?'**
  String get promptAiSkillsTesda;

  /// No description provided for @promptPlanNextStep.
  ///
  /// In en, this message translates to:
  /// **'Help me make a practical plan for my next step.'**
  String get promptPlanNextStep;

  /// No description provided for @promptBuildSkills.
  ///
  /// In en, this message translates to:
  /// **'What skills should I build for the kind of work I want?'**
  String get promptBuildSkills;

  /// No description provided for @chatPrivacyNote.
  ///
  /// In en, this message translates to:
  /// **'Messages are sent to Google Gemini. For career questions, limited skills and job matches from your latest resume or certificate scan may also be shared to personalize suggestions.'**
  String get chatPrivacyNote;

  /// No description provided for @couldNotLoadPeople.
  ///
  /// In en, this message translates to:
  /// **'Could not load people.'**
  String get couldNotLoadPeople;

  /// No description provided for @noActiveMembers.
  ///
  /// In en, this message translates to:
  /// **'No active members found.'**
  String get noActiveMembers;

  /// No description provided for @messagesCouldNotSync.
  ///
  /// In en, this message translates to:
  /// **'Messages could not sync. Retrying…'**
  String get messagesCouldNotSync;

  /// No description provided for @couldNotLoadMessagesRetry.
  ///
  /// In en, this message translates to:
  /// **'Could not load messages. Tap to retry.'**
  String get couldNotLoadMessagesRetry;

  /// No description provided for @noMessagesYet.
  ///
  /// In en, this message translates to:
  /// **'No messages yet. Say hello.'**
  String get noMessagesYet;

  /// No description provided for @attachmentSent.
  ///
  /// In en, this message translates to:
  /// **'Attachment sent and saved in this conversation.'**
  String get attachmentSent;

  /// No description provided for @photoAttachedSend.
  ///
  /// In en, this message translates to:
  /// **'Photo attached. Tap Send to share it.'**
  String get photoAttachedSend;

  /// No description provided for @photoRecoveredSend.
  ///
  /// In en, this message translates to:
  /// **'Your photo was recovered. Tap Send to share it.'**
  String get photoRecoveredSend;

  /// No description provided for @chooseFilesOrPhotos.
  ///
  /// In en, this message translates to:
  /// **'Choose files or photos'**
  String get chooseFilesOrPhotos;

  /// No description provided for @choosePicture.
  ///
  /// In en, this message translates to:
  /// **'Choose a picture'**
  String get choosePicture;

  /// No description provided for @takePicture.
  ///
  /// In en, this message translates to:
  /// **'Take a picture'**
  String get takePicture;

  /// No description provided for @attachmentSaved.
  ///
  /// In en, this message translates to:
  /// **'Attachment saved.'**
  String get attachmentSaved;

  /// No description provided for @previewUnavailableTapSave.
  ///
  /// In en, this message translates to:
  /// **'Preview unavailable. Tap to save the image.'**
  String get previewUnavailableTapSave;

  /// No description provided for @loadingPhoto.
  ///
  /// In en, this message translates to:
  /// **'Loading photo…'**
  String get loadingPhoto;

  /// No description provided for @attachLimit.
  ///
  /// In en, this message translates to:
  /// **'You can attach up to {count} files per message.'**
  String attachLimit(int count);

  /// No description provided for @attachmentTooLarge.
  ///
  /// In en, this message translates to:
  /// **'{file} is larger than 10 MB.'**
  String attachmentTooLarge(String file);

  /// No description provided for @attachmentNameTapSave.
  ///
  /// In en, this message translates to:
  /// **'{name} · tap to save'**
  String attachmentNameTapSave(String name);

  /// No description provided for @messageWorkspaceEyebrow.
  ///
  /// In en, this message translates to:
  /// **'WORKSPACE · PEOPLE'**
  String get messageWorkspaceEyebrow;

  /// No description provided for @messagePeopleDescription.
  ///
  /// In en, this message translates to:
  /// **'Message administrators, trainers, and other active Tuklas members.'**
  String get messagePeopleDescription;

  /// No description provided for @couldNotSendMessage.
  ///
  /// In en, this message translates to:
  /// **'Could not send message. Check your connection and try again.'**
  String get couldNotSendMessage;

  /// No description provided for @couldNotOpenFiles.
  ///
  /// In en, this message translates to:
  /// **'Could not open your files. Please try again.'**
  String get couldNotOpenFiles;

  /// No description provided for @couldNotAccessImage.
  ///
  /// In en, this message translates to:
  /// **'Could not access the image. Check app permissions and try again.'**
  String get couldNotAccessImage;

  /// No description provided for @cameraNoPhoto.
  ///
  /// In en, this message translates to:
  /// **'The camera did not return a photo. Please take it again.'**
  String get cameraNoPhoto;

  /// No description provided for @couldNotRecoverPhoto.
  ///
  /// In en, this message translates to:
  /// **'Could not recover the photo. Please take it again.'**
  String get couldNotRecoverPhoto;

  /// No description provided for @couldNotSaveAttachment.
  ///
  /// In en, this message translates to:
  /// **'Could not save this attachment. Please try again.'**
  String get couldNotSaveAttachment;

  /// No description provided for @noMessagesRetry.
  ///
  /// In en, this message translates to:
  /// **'Could not load messages. Tap to retry.'**
  String get noMessagesRetry;

  /// No description provided for @couldNotLoadScanHistory.
  ///
  /// In en, this message translates to:
  /// **'Could not load your scan history.'**
  String get couldNotLoadScanHistory;

  /// No description provided for @fileAccessDenied.
  ///
  /// In en, this message translates to:
  /// **'File access was denied. Allow access and try again.'**
  String get fileAccessDenied;

  /// No description provided for @fileSelectionCanceled.
  ///
  /// In en, this message translates to:
  /// **'File selection canceled.'**
  String get fileSelectionCanceled;

  /// No description provided for @fileOverTenMb.
  ///
  /// In en, this message translates to:
  /// **'{file} is over 10 MB.'**
  String fileOverTenMb(String file);

  /// No description provided for @fileCouldNotRead.
  ///
  /// In en, this message translates to:
  /// **'{file} could not be read. Please choose it again.'**
  String fileCouldNotRead(String file);

  /// No description provided for @maxFiveScans.
  ///
  /// In en, this message translates to:
  /// **'Up to five files can be scanned at a time.'**
  String get maxFiveScans;

  /// No description provided for @fileAlreadyInScanList.
  ///
  /// In en, this message translates to:
  /// **'That file is already in the scan list.'**
  String get fileAlreadyInScanList;

  /// No description provided for @photoOverTenMb.
  ///
  /// In en, this message translates to:
  /// **'The photo is over 10 MB. Try again with better lighting.'**
  String get photoOverTenMb;

  /// No description provided for @photoAlreadyInScanList.
  ///
  /// In en, this message translates to:
  /// **'This exact photo is already in your scan list.'**
  String get photoAlreadyInScanList;

  /// No description provided for @photoCouldNotAdd.
  ///
  /// In en, this message translates to:
  /// **'The photo could not be added.'**
  String get photoCouldNotAdd;

  /// No description provided for @photoAddedReady.
  ///
  /// In en, this message translates to:
  /// **'Photo added to Ready to scan. Review it, agree to the scan, then tap Scan.'**
  String get photoAddedReady;

  /// No description provided for @photoAddedReplacement.
  ///
  /// In en, this message translates to:
  /// **'Photo added to Ready to scan as the replacement.'**
  String get photoAddedReplacement;

  /// No description provided for @capturedPhotoUnreadable.
  ///
  /// In en, this message translates to:
  /// **'The captured photo could not be read. Please try again.'**
  String get capturedPhotoUnreadable;

  /// No description provided for @cameraCaptureCanceled.
  ///
  /// In en, this message translates to:
  /// **'Camera capture canceled.'**
  String get cameraCaptureCanceled;

  /// No description provided for @cameraAccessDenied.
  ///
  /// In en, this message translates to:
  /// **'Camera access was denied. Allow camera access in Settings and try again.'**
  String get cameraAccessDenied;

  /// No description provided for @cameraCouldNotOpen.
  ///
  /// In en, this message translates to:
  /// **'Could not open the camera. Please try again.'**
  String get cameraCouldNotOpen;

  /// No description provided for @previousCameraPhotoNotRestored.
  ///
  /// In en, this message translates to:
  /// **'A previous camera photo could not be restored. Please take it again.'**
  String get previousCameraPhotoNotRestored;

  /// No description provided for @chooseDocumentFirst.
  ///
  /// In en, this message translates to:
  /// **'Choose a document first.'**
  String get chooseDocumentFirst;

  /// No description provided for @agreeBeforeGeminiScan.
  ///
  /// In en, this message translates to:
  /// **'Please agree before sending the selected files to Gemini.'**
  String get agreeBeforeGeminiScan;

  /// No description provided for @scanDocumentsCount.
  ///
  /// In en, this message translates to:
  /// **'{count} {count, plural, one{document} other{documents}} scanned.'**
  String scanDocumentsCount(int count);

  /// No description provided for @scansRemain.
  ///
  /// In en, this message translates to:
  /// **'{scanned} of {total} scanned; {remaining} remain. Fix the issue and retry the remaining files.'**
  String scansRemain(int scanned, int total, int remaining);

  /// No description provided for @remainingDocuments.
  ///
  /// In en, this message translates to:
  /// **'{count} remaining document(s)'**
  String remainingDocuments(int count);

  /// No description provided for @documentScanCanceled.
  ///
  /// In en, this message translates to:
  /// **'Document scan canceled. Remaining files are ready to retry.'**
  String get documentScanCanceled;

  /// No description provided for @someScansFailed.
  ///
  /// In en, this message translates to:
  /// **'Some scans failed. Check Notifications for details.'**
  String get someScansFailed;

  /// No description provided for @duplicateScanReused.
  ///
  /// In en, this message translates to:
  /// **'This exact file was scanned recently; its saved result was reused without creating a duplicate.'**
  String get duplicateScanReused;

  /// No description provided for @documentsScannedSuccessfully.
  ///
  /// In en, this message translates to:
  /// **'{count} document(s) scanned successfully.'**
  String documentsScannedSuccessfully(int count);

  /// No description provided for @readyToScan.
  ///
  /// In en, this message translates to:
  /// **'Ready to scan'**
  String get readyToScan;

  /// No description provided for @scanFileLimits.
  ///
  /// In en, this message translates to:
  /// **'PDF, DOCX or image · up to 10 MB each · up to 5 files per scan'**
  String get scanFileLimits;

  /// No description provided for @keyDetailsFound.
  ///
  /// In en, this message translates to:
  /// **'Key details found'**
  String get keyDetailsFound;

  /// No description provided for @documentEvidence.
  ///
  /// In en, this message translates to:
  /// **'Document evidence'**
  String get documentEvidence;

  /// No description provided for @check.
  ///
  /// In en, this message translates to:
  /// **'Check'**
  String get check;

  /// No description provided for @scannerChooseDocumentLong.
  ///
  /// In en, this message translates to:
  /// **'Select a readable PDF, DOCX, or image, or take a clear photo. The scanner can summarize many kinds of documents.'**
  String get scannerChooseDocumentLong;

  /// No description provided for @scannerReviewResultsDescription.
  ///
  /// In en, this message translates to:
  /// **'Review the summary and key details. Career and training suggestions appear when the document supports them; confirm important details with the provider.'**
  String get scannerReviewResultsDescription;

  /// No description provided for @geminiScannerDescription.
  ///
  /// In en, this message translates to:
  /// **'Gemini summarizes readable documents and lists key details. When relevant, it can connect resume or certificate evidence to careers and training.'**
  String get geminiScannerDescription;

  /// No description provided for @scannerPrivacyLong.
  ///
  /// In en, this message translates to:
  /// **'Your file is temporarily stored for the scan and sent to Google Gemini. Tuklas deletes the uploaded file after processing; your summary and readable facts stay in your account. Resume skills are added to your profile only for resume and certificate scans.'**
  String get scannerPrivacyLong;

  /// No description provided for @keyDetails.
  ///
  /// In en, this message translates to:
  /// **'Key details found'**
  String get keyDetails;

  /// No description provided for @scanSummary.
  ///
  /// In en, this message translates to:
  /// **'Summary'**
  String get scanSummary;

  /// No description provided for @name.
  ///
  /// In en, this message translates to:
  /// **'Name'**
  String get name;

  /// No description provided for @issuingOrganization.
  ///
  /// In en, this message translates to:
  /// **'Issuing organization'**
  String get issuingOrganization;

  /// No description provided for @issueDate.
  ///
  /// In en, this message translates to:
  /// **'Issue date'**
  String get issueDate;

  /// No description provided for @expiryDate.
  ///
  /// In en, this message translates to:
  /// **'Expiry date'**
  String get expiryDate;

  /// No description provided for @signatureVisible.
  ///
  /// In en, this message translates to:
  /// **'Signature visible'**
  String get signatureVisible;

  /// No description provided for @qualification.
  ///
  /// In en, this message translates to:
  /// **'Qualification'**
  String get qualification;

  /// No description provided for @noAdditionalMatchDetails.
  ///
  /// In en, this message translates to:
  /// **'No additional match details are available.'**
  String get noAdditionalMatchDetails;

  /// No description provided for @scanProcessing.
  ///
  /// In en, this message translates to:
  /// **'Processing'**
  String get scanProcessing;

  /// No description provided for @scanCompleted.
  ///
  /// In en, this message translates to:
  /// **'Completed'**
  String get scanCompleted;

  /// No description provided for @scanFailed.
  ///
  /// In en, this message translates to:
  /// **'Failed'**
  String get scanFailed;

  /// No description provided for @document.
  ///
  /// In en, this message translates to:
  /// **'Document'**
  String get document;

  /// No description provided for @documentEvidenceLabel.
  ///
  /// In en, this message translates to:
  /// **'Document evidence'**
  String get documentEvidenceLabel;

  /// No description provided for @checkLabel.
  ///
  /// In en, this message translates to:
  /// **'Check'**
  String get checkLabel;

  /// No description provided for @scannerEyebrow.
  ///
  /// In en, this message translates to:
  /// **'YOUR SKILLS, DISCOVERED'**
  String get scannerEyebrow;

  /// No description provided for @documentScanner.
  ///
  /// In en, this message translates to:
  /// **'Document scanner'**
  String get documentScanner;

  /// No description provided for @understandDocumentNextSteps.
  ///
  /// In en, this message translates to:
  /// **'Understand a document and find next steps.'**
  String get understandDocumentNextSteps;

  /// No description provided for @howToUseScanner.
  ///
  /// In en, this message translates to:
  /// **'How to use the scanner'**
  String get howToUseScanner;

  /// No description provided for @chooseYourDocument.
  ///
  /// In en, this message translates to:
  /// **'Choose your document'**
  String get chooseYourDocument;

  /// No description provided for @scannerChooseDocumentDescription.
  ///
  /// In en, this message translates to:
  /// **'Select a resume, certificate, or training document, or take a clear photo.'**
  String get scannerChooseDocumentDescription;

  /// No description provided for @addGoalOptional.
  ///
  /// In en, this message translates to:
  /// **'Add a goal if you want'**
  String get addGoalOptional;

  /// No description provided for @scannerGoalDescription.
  ///
  /// In en, this message translates to:
  /// **'Tell Gemini what you are aiming for, such as a job or TESDA course.'**
  String get scannerGoalDescription;

  /// No description provided for @reviewAndAgree.
  ///
  /// In en, this message translates to:
  /// **'Review and agree'**
  String get reviewAndAgree;

  /// No description provided for @scannerReviewDescription.
  ///
  /// In en, this message translates to:
  /// **'Check the selected files and consent before they are sent for analysis.'**
  String get scannerReviewDescription;

  /// No description provided for @scanReviewNextSteps.
  ///
  /// In en, this message translates to:
  /// **'Scan, then review your next steps'**
  String get scanReviewNextSteps;

  /// No description provided for @scannerResultsDescription.
  ///
  /// In en, this message translates to:
  /// **'Check suggested jobs, skills to build, and learning resources. Confirm important details with the provider.'**
  String get scannerResultsDescription;

  /// No description provided for @geminiAi.
  ///
  /// In en, this message translates to:
  /// **'Google Gemini AI'**
  String get geminiAi;

  /// No description provided for @geminiDescription.
  ///
  /// In en, this message translates to:
  /// **'Gemini summarizes documents, identifies covered skills and topics, and suggests related careers and training.'**
  String get geminiDescription;

  /// No description provided for @scannerPrivacyDescription.
  ///
  /// In en, this message translates to:
  /// **'Your file is temporarily stored for the scan and sent to Google Gemini. Tuklas deletes the uploaded file after processing; your scan summary stays in your account.'**
  String get scannerPrivacyDescription;

  /// No description provided for @scannerAiNotice.
  ///
  /// In en, this message translates to:
  /// **'Gemini is an AI and can get details or TESDA matches wrong. Check the result and confirm training and assessment requirements with TESDA or the provider.'**
  String get scannerAiNotice;

  /// No description provided for @chooseMultipleFiles.
  ///
  /// In en, this message translates to:
  /// **'Choose multiple files or images'**
  String get chooseMultipleFiles;

  /// No description provided for @takeDocumentPhoto.
  ///
  /// In en, this message translates to:
  /// **'Take document photo'**
  String get takeDocumentPhoto;

  /// No description provided for @geminiFocusOptional.
  ///
  /// In en, this message translates to:
  /// **'What should Gemini focus on? (optional)'**
  String get geminiFocusOptional;

  /// No description provided for @scannerExamplePrompt.
  ///
  /// In en, this message translates to:
  /// **'Example: Match my certificate to a TESDA qualification and explain why.'**
  String get scannerExamplePrompt;

  /// No description provided for @agreeSendFilesToGemini.
  ///
  /// In en, this message translates to:
  /// **'I agree to send the selected files to Google Gemini to scan them.'**
  String get agreeSendFilesToGemini;

  /// No description provided for @scanningDocuments.
  ///
  /// In en, this message translates to:
  /// **'Scanning documents…'**
  String get scanningDocuments;

  /// No description provided for @scanDocumentCount.
  ///
  /// In en, this message translates to:
  /// **'Scan {count} document(s)'**
  String scanDocumentCount(int count);

  /// No description provided for @cancelingCurrentDocument.
  ///
  /// In en, this message translates to:
  /// **'Canceling after the current document finishes…'**
  String get cancelingCurrentDocument;

  /// No description provided for @canceling.
  ///
  /// In en, this message translates to:
  /// **'Canceling…'**
  String get canceling;

  /// No description provided for @cancelRemainingScans.
  ///
  /// In en, this message translates to:
  /// **'Cancel remaining scans'**
  String get cancelRemainingScans;

  /// No description provided for @yourScans.
  ///
  /// In en, this message translates to:
  /// **'Your scans'**
  String get yourScans;

  /// No description provided for @retryLoadingScans.
  ///
  /// In en, this message translates to:
  /// **'Retry loading scans'**
  String get retryLoadingScans;

  /// No description provided for @completedScansEmpty.
  ///
  /// In en, this message translates to:
  /// **'Your completed scans will appear here.'**
  String get completedScansEmpty;

  /// No description provided for @latestScan.
  ///
  /// In en, this message translates to:
  /// **'Latest scan'**
  String get latestScan;

  /// No description provided for @openTuklas.
  ///
  /// In en, this message translates to:
  /// **'Open in Tuklas'**
  String get openTuklas;

  /// No description provided for @skillsFound.
  ///
  /// In en, this message translates to:
  /// **'Skills found'**
  String get skillsFound;

  /// No description provided for @careerMatches.
  ///
  /// In en, this message translates to:
  /// **'Career matches'**
  String get careerMatches;

  /// No description provided for @jobRecommendations.
  ///
  /// In en, this message translates to:
  /// **'Job recommendations'**
  String get jobRecommendations;

  /// No description provided for @skillsToBuild.
  ///
  /// In en, this message translates to:
  /// **'Skills to build'**
  String get skillsToBuild;

  /// No description provided for @tesdaTrainingConsider.
  ///
  /// In en, this message translates to:
  /// **'TESDA training to consider'**
  String get tesdaTrainingConsider;

  /// No description provided for @learningRecommendations.
  ///
  /// In en, this message translates to:
  /// **'Learning recommendations'**
  String get learningRecommendations;

  /// No description provided for @nextSteps.
  ///
  /// In en, this message translates to:
  /// **'Next steps'**
  String get nextSteps;

  /// No description provided for @readableDocumentDetails.
  ///
  /// In en, this message translates to:
  /// **'Readable document details'**
  String get readableDocumentDetails;

  /// No description provided for @notReadableOrShown.
  ///
  /// In en, this message translates to:
  /// **'Not readable or not shown'**
  String get notReadableOrShown;

  /// No description provided for @identityCrossCheck.
  ///
  /// In en, this message translates to:
  /// **'Identity cross-check'**
  String get identityCrossCheck;

  /// No description provided for @requirementCrossCheck.
  ///
  /// In en, this message translates to:
  /// **'Requirement cross-check'**
  String get requirementCrossCheck;

  /// No description provided for @noReadableComparison.
  ///
  /// In en, this message translates to:
  /// **'No readable comparison available'**
  String get noReadableComparison;

  /// No description provided for @recommendation.
  ///
  /// In en, this message translates to:
  /// **'Recommendation'**
  String get recommendation;

  /// No description provided for @openInTuklas.
  ///
  /// In en, this message translates to:
  /// **'Open in Tuklas'**
  String get openInTuklas;

  /// No description provided for @careerPossibilities.
  ///
  /// In en, this message translates to:
  /// **'CAREER POSSIBILITIES'**
  String get careerPossibilities;

  /// No description provided for @pesoJobMatches.
  ///
  /// In en, this message translates to:
  /// **'PESO job matches'**
  String get pesoJobMatches;

  /// No description provided for @careerSuggestionsScans.
  ///
  /// In en, this message translates to:
  /// **'Career suggestions based on scanned resumes and certificates.'**
  String get careerSuggestionsScans;

  /// No description provided for @noJobMatchesYet.
  ///
  /// In en, this message translates to:
  /// **'No job matches yet. Scan a resume or certificate to get personalized career guidance.'**
  String get noJobMatchesYet;

  /// No description provided for @suggestionsGuidanceNotGuarantees.
  ///
  /// In en, this message translates to:
  /// **'Suggestions are guidance, not guarantees of employment.'**
  String get suggestionsGuidanceNotGuarantees;

  /// No description provided for @suggestedJobs.
  ///
  /// In en, this message translates to:
  /// **'Suggested jobs'**
  String get suggestedJobs;

  /// No description provided for @freeLearningPractice.
  ///
  /// In en, this message translates to:
  /// **'Free learning and practice'**
  String get freeLearningPractice;

  /// No description provided for @documentedSkills.
  ///
  /// In en, this message translates to:
  /// **'Documented skills'**
  String get documentedSkills;

  /// No description provided for @whyMayFit.
  ///
  /// In en, this message translates to:
  /// **'Why it may fit'**
  String get whyMayFit;

  /// No description provided for @evidence.
  ///
  /// In en, this message translates to:
  /// **'Evidence'**
  String get evidence;

  /// No description provided for @noMatchDetails.
  ///
  /// In en, this message translates to:
  /// **'No additional match details are available.'**
  String get noMatchDetails;

  /// No description provided for @couldNotLoadPesoMatches.
  ///
  /// In en, this message translates to:
  /// **'Could not load PESO job matches. Check your connection and retry.'**
  String get couldNotLoadPesoMatches;

  /// No description provided for @patchNotesDescription.
  ///
  /// In en, this message translates to:
  /// **'Website and mobile app updates, newest first.'**
  String get patchNotesDescription;

  /// No description provided for @couldNotLoadPatchNotes.
  ///
  /// In en, this message translates to:
  /// **'Could not load patch notes. Check your connection and retry.'**
  String get couldNotLoadPatchNotes;

  /// No description provided for @noPublishedUpdates.
  ///
  /// In en, this message translates to:
  /// **'No updates have been published yet.'**
  String get noPublishedUpdates;

  /// No description provided for @update.
  ///
  /// In en, this message translates to:
  /// **'Update'**
  String get update;

  /// No description provided for @tuklasUpdate.
  ///
  /// In en, this message translates to:
  /// **'Tuklas update'**
  String get tuklasUpdate;

  /// No description provided for @tryAgainToLoad.
  ///
  /// In en, this message translates to:
  /// **'Try again'**
  String get tryAgainToLoad;

  /// No description provided for @delete.
  ///
  /// In en, this message translates to:
  /// **'Delete'**
  String get delete;

  /// No description provided for @underReview.
  ///
  /// In en, this message translates to:
  /// **'Under review'**
  String get underReview;

  /// No description provided for @markHired.
  ///
  /// In en, this message translates to:
  /// **'Mark hired'**
  String get markHired;

  /// No description provided for @notSelected.
  ///
  /// In en, this message translates to:
  /// **'Not selected'**
  String get notSelected;

  /// No description provided for @applyForJob.
  ///
  /// In en, this message translates to:
  /// **'Apply for this job'**
  String get applyForJob;

  /// No description provided for @approveForCenter.
  ///
  /// In en, this message translates to:
  /// **'Approve for center'**
  String get approveForCenter;

  /// No description provided for @approveAsTrainer.
  ///
  /// In en, this message translates to:
  /// **'Approve as trainer'**
  String get approveAsTrainer;

  /// No description provided for @returnToDraft.
  ///
  /// In en, this message translates to:
  /// **'Return to draft'**
  String get returnToDraft;

  /// No description provided for @addCourse.
  ///
  /// In en, this message translates to:
  /// **'Add course'**
  String get addCourse;

  /// No description provided for @openApplicationLink.
  ///
  /// In en, this message translates to:
  /// **'Open application link'**
  String get openApplicationLink;

  /// No description provided for @recommendedByPeso.
  ///
  /// In en, this message translates to:
  /// **'Recommended by PESO'**
  String get recommendedByPeso;

  /// No description provided for @hiredRecordedByPeso.
  ///
  /// In en, this message translates to:
  /// **'Hired · recorded by PESO'**
  String get hiredRecordedByPeso;

  /// No description provided for @noScholarshipApplications.
  ///
  /// In en, this message translates to:
  /// **'You have not applied to a scholarship yet.'**
  String get noScholarshipApplications;

  /// No description provided for @cancelApplication.
  ///
  /// In en, this message translates to:
  /// **'Cancel application'**
  String get cancelApplication;

  /// No description provided for @browsePhilJobNet.
  ///
  /// In en, this message translates to:
  /// **'Browse live DOLE PhilJobNet vacancies'**
  String get browsePhilJobNet;

  /// No description provided for @searchSection.
  ///
  /// In en, this message translates to:
  /// **'Search {section}'**
  String searchSection(String section);

  /// No description provided for @clearSearch.
  ///
  /// In en, this message translates to:
  /// **'Clear search'**
  String get clearSearch;

  /// No description provided for @search.
  ///
  /// In en, this message translates to:
  /// **'Search'**
  String get search;

  /// No description provided for @loadingCurrentResults.
  ///
  /// In en, this message translates to:
  /// **'Loading current results…'**
  String get loadingCurrentResults;

  /// No description provided for @resultsCount.
  ///
  /// In en, this message translates to:
  /// **'{count} results'**
  String resultsCount(int count);

  /// No description provided for @applyForScholarship.
  ///
  /// In en, this message translates to:
  /// **'Apply for scholarship'**
  String get applyForScholarship;

  /// No description provided for @whyApplying.
  ///
  /// In en, this message translates to:
  /// **'Why are you applying?'**
  String get whyApplying;

  /// No description provided for @chooseUpToFiveDocuments.
  ///
  /// In en, this message translates to:
  /// **'Choose up to five documents.'**
  String get chooseUpToFiveDocuments;

  /// No description provided for @attachRequirements.
  ///
  /// In en, this message translates to:
  /// **'Attach requirements'**
  String get attachRequirements;

  /// No description provided for @submitApplication.
  ///
  /// In en, this message translates to:
  /// **'Submit application'**
  String get submitApplication;

  /// No description provided for @add.
  ///
  /// In en, this message translates to:
  /// **'Add'**
  String get add;

  /// No description provided for @removeAssignment.
  ///
  /// In en, this message translates to:
  /// **'Remove assignment'**
  String get removeAssignment;

  /// No description provided for @requestCourse.
  ///
  /// In en, this message translates to:
  /// **'Request course'**
  String get requestCourse;

  /// No description provided for @applyAndSendDocuments.
  ///
  /// In en, this message translates to:
  /// **'Apply and send documents'**
  String get applyAndSendDocuments;

  /// No description provided for @withdraw.
  ///
  /// In en, this message translates to:
  /// **'Withdraw'**
  String get withdraw;

  /// No description provided for @accept.
  ///
  /// In en, this message translates to:
  /// **'Accept'**
  String get accept;

  /// No description provided for @decline.
  ///
  /// In en, this message translates to:
  /// **'Decline'**
  String get decline;

  /// No description provided for @assignIntake.
  ///
  /// In en, this message translates to:
  /// **'Assign intake'**
  String get assignIntake;

  /// No description provided for @recommend.
  ///
  /// In en, this message translates to:
  /// **'Recommend'**
  String get recommend;

  /// No description provided for @recommendToCareerSeeker.
  ///
  /// In en, this message translates to:
  /// **'Recommend to career seeker'**
  String get recommendToCareerSeeker;

  /// No description provided for @reasonNonSelection.
  ///
  /// In en, this message translates to:
  /// **'Reason for non-selection'**
  String get reasonNonSelection;

  /// No description provided for @feedbackCareerSeeker.
  ///
  /// In en, this message translates to:
  /// **'Feedback for the career seeker'**
  String get feedbackCareerSeeker;

  /// No description provided for @sendDecision.
  ///
  /// In en, this message translates to:
  /// **'Send decision'**
  String get sendDecision;

  /// No description provided for @assignTesdaStaff.
  ///
  /// In en, this message translates to:
  /// **'Assign TESDA staff'**
  String get assignTesdaStaff;

  /// No description provided for @deleteScholarshipConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete scholarship?'**
  String get deleteScholarshipConfirm;

  /// No description provided for @deleteTrainingCenterConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete training center?'**
  String get deleteTrainingCenterConfirm;

  /// No description provided for @deleteCourseConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete course?'**
  String get deleteCourseConfirm;

  /// No description provided for @deleteJobOpeningConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete job opening?'**
  String get deleteJobOpeningConfirm;

  /// No description provided for @deleteTrainingSessionConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete training session?'**
  String get deleteTrainingSessionConfirm;

  /// No description provided for @deleteAssessmentConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete assessment record?'**
  String get deleteAssessmentConfirm;

  /// No description provided for @reasonDeclining.
  ///
  /// In en, this message translates to:
  /// **'Reason for declining'**
  String get reasonDeclining;

  /// No description provided for @reasonCareerSeeker.
  ///
  /// In en, this message translates to:
  /// **'Reason for the Career Seeker'**
  String get reasonCareerSeeker;

  /// No description provided for @confirmDecline.
  ///
  /// In en, this message translates to:
  /// **'Confirm decline'**
  String get confirmDecline;

  /// No description provided for @fileTypeCannotPreview.
  ///
  /// In en, this message translates to:
  /// **'This file type cannot be previewed.'**
  String get fileTypeCannotPreview;

  /// No description provided for @closePreview.
  ///
  /// In en, this message translates to:
  /// **'Close preview'**
  String get closePreview;

  /// No description provided for @imageCouldNotDisplay.
  ///
  /// In en, this message translates to:
  /// **'This image could not be displayed.'**
  String get imageCouldNotDisplay;

  /// No description provided for @couldNotPreviewApplicationDocument.
  ///
  /// In en, this message translates to:
  /// **'Could not preview the application document.'**
  String get couldNotPreviewApplicationDocument;

  /// No description provided for @scholarshipApplication.
  ///
  /// In en, this message translates to:
  /// **'Apply for scholarship'**
  String get scholarshipApplication;

  /// No description provided for @applicationMessage.
  ///
  /// In en, this message translates to:
  /// **'Why are you applying?'**
  String get applicationMessage;

  /// No description provided for @youHaveNotAppliedScholarship.
  ///
  /// In en, this message translates to:
  /// **'You have not applied to a scholarship yet.'**
  String get youHaveNotAppliedScholarship;

  /// No description provided for @updateJobApplication.
  ///
  /// In en, this message translates to:
  /// **'Update job application'**
  String get updateJobApplication;

  /// No description provided for @noNotificationsYet.
  ///
  /// In en, this message translates to:
  /// **'No notifications yet.'**
  String get noNotificationsYet;

  /// No description provided for @noProgramsYet.
  ///
  /// In en, this message translates to:
  /// **'No programs yet.'**
  String get noProgramsYet;

  /// No description provided for @createAccount.
  ///
  /// In en, this message translates to:
  /// **'Create account'**
  String get createAccount;

  /// No description provided for @deletedAccounts.
  ///
  /// In en, this message translates to:
  /// **'Deleted accounts'**
  String get deletedAccounts;

  /// No description provided for @noAccountsFound.
  ///
  /// In en, this message translates to:
  /// **'No accounts found.'**
  String get noAccountsFound;

  /// No description provided for @searchQualifications.
  ///
  /// In en, this message translates to:
  /// **'Search qualifications'**
  String get searchQualifications;

  /// No description provided for @previousProviders.
  ///
  /// In en, this message translates to:
  /// **'Previous providers'**
  String get previousProviders;

  /// No description provided for @moreProviders.
  ///
  /// In en, this message translates to:
  /// **'More providers'**
  String get moreProviders;

  /// No description provided for @tesdaTrainingServices.
  ///
  /// In en, this message translates to:
  /// **'TESDA training and services'**
  String get tesdaTrainingServices;

  /// No description provided for @trainingCourses.
  ///
  /// In en, this message translates to:
  /// **'Training courses'**
  String get trainingCourses;

  /// No description provided for @courseRequestSubmitted.
  ///
  /// In en, this message translates to:
  /// **'Course request submitted.'**
  String get courseRequestSubmitted;

  /// No description provided for @requestSubmitted.
  ///
  /// In en, this message translates to:
  /// **'Request submitted.'**
  String get requestSubmitted;

  /// No description provided for @chooseOneOption.
  ///
  /// In en, this message translates to:
  /// **'Choose one option'**
  String get chooseOneOption;

  /// No description provided for @saveAssessment.
  ///
  /// In en, this message translates to:
  /// **'Save assessment'**
  String get saveAssessment;

  /// No description provided for @walkMeThrough.
  ///
  /// In en, this message translates to:
  /// **'Walk me through this'**
  String get walkMeThrough;

  /// No description provided for @send.
  ///
  /// In en, this message translates to:
  /// **'Send'**
  String get send;

  /// No description provided for @later.
  ///
  /// In en, this message translates to:
  /// **'Later'**
  String get later;

  /// No description provided for @downloadUpdate.
  ///
  /// In en, this message translates to:
  /// **'Download update'**
  String get downloadUpdate;

  /// No description provided for @updateAvailable.
  ///
  /// In en, this message translates to:
  /// **'Tuklas update available'**
  String get updateAvailable;

  /// No description provided for @whatsNew.
  ///
  /// In en, this message translates to:
  /// **'What’s new in {version}'**
  String whatsNew(String version);

  /// No description provided for @selectDateOfBirth.
  ///
  /// In en, this message translates to:
  /// **'Select your date of birth'**
  String get selectDateOfBirth;

  /// No description provided for @chooseMunicipality.
  ///
  /// In en, this message translates to:
  /// **'Choose municipality'**
  String get chooseMunicipality;

  /// No description provided for @chooseMunicipalityFirst.
  ///
  /// In en, this message translates to:
  /// **'Choose municipality first'**
  String get chooseMunicipalityFirst;

  /// No description provided for @chooseBarangay.
  ///
  /// In en, this message translates to:
  /// **'Choose barangay'**
  String get chooseBarangay;

  /// No description provided for @chooseAgeRangeDate.
  ///
  /// In en, this message translates to:
  /// **'Choose a date between ages 18 and 30'**
  String get chooseAgeRangeDate;

  /// No description provided for @required.
  ///
  /// In en, this message translates to:
  /// **'Required'**
  String get required;

  /// No description provided for @editAccountProfile.
  ///
  /// In en, this message translates to:
  /// **'Edit account profile'**
  String get editAccountProfile;

  /// No description provided for @editProfile.
  ///
  /// In en, this message translates to:
  /// **'Edit profile'**
  String get editProfile;

  /// No description provided for @yourProfile.
  ///
  /// In en, this message translates to:
  /// **'YOUR PROFILE'**
  String get yourProfile;

  /// No description provided for @profileGrowsWithYou.
  ///
  /// In en, this message translates to:
  /// **'A profile that grows with you'**
  String get profileGrowsWithYou;

  /// No description provided for @reduceMotion.
  ///
  /// In en, this message translates to:
  /// **'Reduce motion'**
  String get reduceMotion;

  /// No description provided for @reduceMotionDescription.
  ///
  /// In en, this message translates to:
  /// **'Turn off transitions for smoother use on budget devices.'**
  String get reduceMotionDescription;

  /// No description provided for @passwordAndSecurity.
  ///
  /// In en, this message translates to:
  /// **'Password and security'**
  String get passwordAndSecurity;

  /// No description provided for @manageAccountSecurity.
  ///
  /// In en, this message translates to:
  /// **'Manage your account details, password, and two-factor authentication here.'**
  String get manageAccountSecurity;

  /// No description provided for @openAccountSecurity.
  ///
  /// In en, this message translates to:
  /// **'Open account & security'**
  String get openAccountSecurity;

  /// No description provided for @signOutDescription.
  ///
  /// In en, this message translates to:
  /// **'Sign out of your Tuklas account on this device.'**
  String get signOutDescription;

  /// No description provided for @careerSeekerDetails.
  ///
  /// In en, this message translates to:
  /// **'Career Seeker details'**
  String get careerSeekerDetails;

  /// No description provided for @careerSeekerProfileDescription.
  ///
  /// In en, this message translates to:
  /// **'Tell us about your education and interests. Tuklas uses these to suggest careers and TESDA training. Suggestions are guidance; they do not guarantee a job, admission, or a training place.'**
  String get careerSeekerProfileDescription;

  /// No description provided for @careerSeekerAgeNotice.
  ///
  /// In en, this message translates to:
  /// **'Career Seeker accounts are for people aged 18 to 30.'**
  String get careerSeekerAgeNotice;

  /// No description provided for @educationalAttainment.
  ///
  /// In en, this message translates to:
  /// **'Educational attainment'**
  String get educationalAttainment;

  /// No description provided for @currentStatus.
  ///
  /// In en, this message translates to:
  /// **'Current status'**
  String get currentStatus;

  /// No description provided for @selectOne.
  ///
  /// In en, this message translates to:
  /// **'Select one'**
  String get selectOne;

  /// No description provided for @interestsAndLivelihood.
  ///
  /// In en, this message translates to:
  /// **'Interests and livelihood interests'**
  String get interestsAndLivelihood;

  /// No description provided for @livelihoodExamples.
  ///
  /// In en, this message translates to:
  /// **'For example: cooking, farming, electronics, selling online'**
  String get livelihoodExamples;

  /// No description provided for @saveDetails.
  ///
  /// In en, this message translates to:
  /// **'Save details'**
  String get saveDetails;

  /// No description provided for @accountSaved.
  ///
  /// In en, this message translates to:
  /// **'Your details were saved.'**
  String get accountSaved;

  /// No description provided for @couldNotReachServer.
  ///
  /// In en, this message translates to:
  /// **'Could not reach the server. Check your connection and try again.'**
  String get couldNotReachServer;

  /// No description provided for @serverAddressAndConnectionError.
  ///
  /// In en, this message translates to:
  /// **'Could not reach the server. Check the address and your connection.'**
  String get serverAddressAndConnectionError;

  /// No description provided for @serverUnavailableDetail.
  ///
  /// In en, this message translates to:
  /// **'Could not load. Check your connection.'**
  String get serverUnavailableDetail;

  /// No description provided for @couldNotConnectTryAgain.
  ///
  /// In en, this message translates to:
  /// **'Could not connect. Please try again.'**
  String get couldNotConnectTryAgain;

  /// No description provided for @couldNotOpenPage.
  ///
  /// In en, this message translates to:
  /// **'Could not open this page. Please try again.'**
  String get couldNotOpenPage;

  /// No description provided for @couldNotOpenResource.
  ///
  /// In en, this message translates to:
  /// **'Could not open this resource.'**
  String get couldNotOpenResource;

  /// No description provided for @couldNotOpenTesdaResource.
  ///
  /// In en, this message translates to:
  /// **'Could not open the TESDA resource.'**
  String get couldNotOpenTesdaResource;

  /// No description provided for @couldNotLoadTryAgain.
  ///
  /// In en, this message translates to:
  /// **'Could not load. Try again'**
  String get couldNotLoadTryAgain;

  /// No description provided for @saved.
  ///
  /// In en, this message translates to:
  /// **'Saved.'**
  String get saved;

  /// No description provided for @couldNotSaveTryAgain.
  ///
  /// In en, this message translates to:
  /// **'Could not save. Try again.'**
  String get couldNotSaveTryAgain;

  /// No description provided for @tryAgainButton.
  ///
  /// In en, this message translates to:
  /// **'Try again'**
  String get tryAgainButton;

  /// No description provided for @scanDocument.
  ///
  /// In en, this message translates to:
  /// **'Scan document'**
  String get scanDocument;

  /// No description provided for @openingPage.
  ///
  /// In en, this message translates to:
  /// **'Opening {page}…'**
  String openingPage(String page);

  /// No description provided for @openingOverview.
  ///
  /// In en, this message translates to:
  /// **'Overview'**
  String get openingOverview;

  /// No description provided for @confirmEmailInstructions.
  ///
  /// In en, this message translates to:
  /// **'If you have not verified yet, open the link in your email and check again.'**
  String get confirmEmailInstructions;

  /// No description provided for @chooseLanguage.
  ///
  /// In en, this message translates to:
  /// **'Choose language'**
  String get chooseLanguage;

  /// No description provided for @tuklasOnlineSignInCheckFailed.
  ///
  /// In en, this message translates to:
  /// **'Tuklas is online, but sign-in could not be checked'**
  String get tuklasOnlineSignInCheckFailed;

  /// No description provided for @cannotReachTuklasServer.
  ///
  /// In en, this message translates to:
  /// **'Can’t reach the Tuklas server'**
  String get cannotReachTuklasServer;

  /// No description provided for @serverSignInCheckError.
  ///
  /// In en, this message translates to:
  /// **'Your internet connection is working. The server returned an error while checking your saved sign-in. Please retry in a moment.'**
  String get serverSignInCheckError;

  /// No description provided for @savedSignInOffline.
  ///
  /// In en, this message translates to:
  /// **'Your saved sign-in is still on this device. Tuklas could not connect to its server. It will retry automatically; you can also retry now.'**
  String get savedSignInOffline;

  /// No description provided for @openingCenters.
  ///
  /// In en, this message translates to:
  /// **'Opening Centers…'**
  String get openingCenters;

  /// No description provided for @messagesSearchHint.
  ///
  /// In en, this message translates to:
  /// **'Find a person'**
  String get messagesSearchHint;

  /// No description provided for @tryAgainAction.
  ///
  /// In en, this message translates to:
  /// **'Try again'**
  String get tryAgainAction;

  /// No description provided for @attachFileOrPicture.
  ///
  /// In en, this message translates to:
  /// **'Attach a file or picture'**
  String get attachFileOrPicture;

  /// No description provided for @writeMessage.
  ///
  /// In en, this message translates to:
  /// **'Write a message'**
  String get writeMessage;

  /// No description provided for @sendMessage.
  ///
  /// In en, this message translates to:
  /// **'Send message'**
  String get sendMessage;

  /// No description provided for @askSomething.
  ///
  /// In en, this message translates to:
  /// **'Ask something...'**
  String get askSomething;

  /// No description provided for @sources.
  ///
  /// In en, this message translates to:
  /// **'Sources'**
  String get sources;

  /// No description provided for @reloadConversation.
  ///
  /// In en, this message translates to:
  /// **'Reload conversation'**
  String get reloadConversation;

  /// No description provided for @couldNotOpenSource.
  ///
  /// In en, this message translates to:
  /// **'Could not open this source.'**
  String get couldNotOpenSource;

  /// No description provided for @openResource.
  ///
  /// In en, this message translates to:
  /// **'Open resource'**
  String get openResource;

  /// No description provided for @chooseReplacementFile.
  ///
  /// In en, this message translates to:
  /// **'Choose replacement file or picture'**
  String get chooseReplacementFile;

  /// No description provided for @takeReplacementPhoto.
  ///
  /// In en, this message translates to:
  /// **'Take replacement photo'**
  String get takeReplacementPhoto;

  /// No description provided for @removeFile.
  ///
  /// In en, this message translates to:
  /// **'Remove {file}'**
  String removeFile(String file);

  /// No description provided for @replaceFile.
  ///
  /// In en, this message translates to:
  /// **'Replace {file}'**
  String replaceFile(String file);

  /// No description provided for @openPage.
  ///
  /// In en, this message translates to:
  /// **'Open page'**
  String get openPage;

  /// No description provided for @refresh.
  ///
  /// In en, this message translates to:
  /// **'Refresh'**
  String get refresh;

  /// No description provided for @deleteAccount.
  ///
  /// In en, this message translates to:
  /// **'Delete account'**
  String get deleteAccount;

  /// No description provided for @deleteAccountConfirm.
  ///
  /// In en, this message translates to:
  /// **'Delete this account? It can be restored.'**
  String get deleteAccountConfirm;

  /// No description provided for @restoreAccount.
  ///
  /// In en, this message translates to:
  /// **'Restore account'**
  String get restoreAccount;

  /// No description provided for @restoreAccountConfirm.
  ///
  /// In en, this message translates to:
  /// **'Restore this account?'**
  String get restoreAccountConfirm;

  /// No description provided for @edit.
  ///
  /// In en, this message translates to:
  /// **'Edit'**
  String get edit;

  /// No description provided for @reset.
  ///
  /// In en, this message translates to:
  /// **'Reset'**
  String get reset;

  /// No description provided for @requestPasswordResetForAccount.
  ///
  /// In en, this message translates to:
  /// **'Request a password reset for this account?'**
  String get requestPasswordResetForAccount;

  /// No description provided for @account.
  ///
  /// In en, this message translates to:
  /// **'Account'**
  String get account;

  /// No description provided for @editNameAndEmail.
  ///
  /// In en, this message translates to:
  /// **'Edit name and email'**
  String get editNameAndEmail;

  /// No description provided for @accountDetails.
  ///
  /// In en, this message translates to:
  /// **'Account details'**
  String get accountDetails;

  /// No description provided for @fullName.
  ///
  /// In en, this message translates to:
  /// **'Full name'**
  String get fullName;

  /// No description provided for @changePassword.
  ///
  /// In en, this message translates to:
  /// **'Change password'**
  String get changePassword;

  /// No description provided for @sendVerificationEmail.
  ///
  /// In en, this message translates to:
  /// **'Send verification email'**
  String get sendVerificationEmail;

  /// No description provided for @refreshVerificationStatus.
  ///
  /// In en, this message translates to:
  /// **'Refresh verification status'**
  String get refreshVerificationStatus;

  /// No description provided for @twoFactorAuthentication.
  ///
  /// In en, this message translates to:
  /// **'Two-factor authentication'**
  String get twoFactorAuthentication;

  /// No description provided for @enabled.
  ///
  /// In en, this message translates to:
  /// **'Enabled'**
  String get enabled;

  /// No description provided for @disabled.
  ///
  /// In en, this message translates to:
  /// **'Disabled'**
  String get disabled;

  /// No description provided for @chooseYouthAccount.
  ///
  /// In en, this message translates to:
  /// **'Choose youth account'**
  String get chooseYouthAccount;

  /// No description provided for @noActiveYouthAccounts.
  ///
  /// In en, this message translates to:
  /// **'No active youth accounts.'**
  String get noActiveYouthAccounts;

  /// No description provided for @skipWalkthrough.
  ///
  /// In en, this message translates to:
  /// **'Skip walkthrough'**
  String get skipWalkthrough;

  /// No description provided for @back.
  ///
  /// In en, this message translates to:
  /// **'Back'**
  String get back;

  /// No description provided for @letsBegin.
  ///
  /// In en, this message translates to:
  /// **'Let’s begin'**
  String get letsBegin;

  /// No description provided for @stepOf.
  ///
  /// In en, this message translates to:
  /// **'Step {current} of {total}'**
  String stepOf(int current, int total);

  /// No description provided for @assessmentWalkthroughOneTitle.
  ///
  /// In en, this message translates to:
  /// **'Start with a few choices about yourself.'**
  String get assessmentWalkthroughOneTitle;

  /// No description provided for @assessmentWalkthroughOneBody.
  ///
  /// In en, this message translates to:
  /// **'This is not a test. Your answers help Tuklas suggest directions that fit what you like and what you have tried.'**
  String get assessmentWalkthroughOneBody;

  /// No description provided for @assessmentWalkthroughTwoTitle.
  ///
  /// In en, this message translates to:
  /// **'Choose practiced skills or no prior experience.'**
  String get assessmentWalkthroughTwoTitle;

  /// No description provided for @assessmentWalkthroughTwoBody.
  ///
  /// In en, this message translates to:
  /// **'You can begin without a resume or certificate. Select only skills you have actually tried.'**
  String get assessmentWalkthroughTwoBody;

  /// No description provided for @assessmentWalkthroughThreeTitle.
  ///
  /// In en, this message translates to:
  /// **'Pick interests and answer each required choice.'**
  String get assessmentWalkthroughThreeTitle;

  /// No description provided for @assessmentWalkthroughThreeBody.
  ///
  /// In en, this message translates to:
  /// **'Choose fields you want to explore. Use the flexible or “not sure yet” option if you are still deciding.'**
  String get assessmentWalkthroughThreeBody;

  /// No description provided for @assessmentWalkthroughFourTitle.
  ///
  /// In en, this message translates to:
  /// **'Save to see your matches, then choose a next step.'**
  String get assessmentWalkthroughFourTitle;

  /// No description provided for @assessmentWalkthroughFourBody.
  ///
  /// In en, this message translates to:
  /// **'Work, career, and training suggestions appear above the questions. Scanning a resume or certificate is optional.'**
  String get assessmentWalkthroughFourBody;

  /// No description provided for @confirmSetup.
  ///
  /// In en, this message translates to:
  /// **'Confirm setup'**
  String get confirmSetup;

  /// No description provided for @addToAuthenticator.
  ///
  /// In en, this message translates to:
  /// **'Add to your authenticator'**
  String get addToAuthenticator;

  /// No description provided for @saveRecoveryCodes.
  ///
  /// In en, this message translates to:
  /// **'Save your recovery codes'**
  String get saveRecoveryCodes;

  /// No description provided for @authenticatorSetupInstructions.
  ///
  /// In en, this message translates to:
  /// **'Enter this key in your authenticator, then choose Confirm setup.\n\n{key}'**
  String authenticatorSetupInstructions(String key);

  /// No description provided for @elementaryGraduate.
  ///
  /// In en, this message translates to:
  /// **'Elementary graduate'**
  String get elementaryGraduate;

  /// No description provided for @juniorHighUndergraduate.
  ///
  /// In en, this message translates to:
  /// **'Junior high school undergraduate'**
  String get juniorHighUndergraduate;

  /// No description provided for @juniorHighGraduate.
  ///
  /// In en, this message translates to:
  /// **'Junior high school graduate'**
  String get juniorHighGraduate;

  /// No description provided for @seniorHighUndergraduate.
  ///
  /// In en, this message translates to:
  /// **'Senior high school undergraduate'**
  String get seniorHighUndergraduate;

  /// No description provided for @seniorHighGraduate.
  ///
  /// In en, this message translates to:
  /// **'Senior high school graduate'**
  String get seniorHighGraduate;

  /// No description provided for @vocationalGraduate.
  ///
  /// In en, this message translates to:
  /// **'Vocational or TVET graduate'**
  String get vocationalGraduate;

  /// No description provided for @collegeUndergraduate.
  ///
  /// In en, this message translates to:
  /// **'College undergraduate'**
  String get collegeUndergraduate;

  /// No description provided for @collegeGraduate.
  ///
  /// In en, this message translates to:
  /// **'College graduate'**
  String get collegeGraduate;

  /// No description provided for @postgraduate.
  ///
  /// In en, this message translates to:
  /// **'Postgraduate'**
  String get postgraduate;

  /// No description provided for @student.
  ///
  /// In en, this message translates to:
  /// **'Student'**
  String get student;

  /// No description provided for @employed.
  ///
  /// In en, this message translates to:
  /// **'Employed'**
  String get employed;

  /// No description provided for @selfEmployed.
  ///
  /// In en, this message translates to:
  /// **'Self-employed'**
  String get selfEmployed;

  /// No description provided for @unemployed.
  ///
  /// In en, this message translates to:
  /// **'Unemployed'**
  String get unemployed;

  /// No description provided for @outOfSchool.
  ///
  /// In en, this message translates to:
  /// **'Out of school'**
  String get outOfSchool;

  /// No description provided for @scannerPrivacyDetails.
  ///
  /// In en, this message translates to:
  /// **'Privacy details'**
  String get scannerPrivacyDetails;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['en', 'tl'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'en':
      return AppLocalizationsEn();
    case 'tl':
      return AppLocalizationsTl();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
