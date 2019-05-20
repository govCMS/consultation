# GovCMS Consultation module

AKA: Have your say

## Status

This module is originally developed by Dept Communications DOCA)
and is roadmapped for the D8 GovCMS distribution. Updated
courtesy of the Aust Communications Media Authority (ACMA), Cordelta
and Lil Engine.

## Upgrade from Drupal 7

There is currently no Drupal 7 to 8 upgrade process. It should be
feasible to do with a migration. The field names have changed
but are modelled on the Drupal 7 version. 

## Setup

1. After installing, clone the template submission webform
at /admin/structure/webform/manage/consultation_default/duplicate

2. For convenience, Set this new form on webform field as the
default webform at /admin/structure/types/manage/consultation/fields/node.consultation.field_cons_webform

## Sensitive submissions

Webform defaults to having private file submissions. When a submission is made
public, you should set "Approved for display" on the submission, and ensure
that "Remain Private" is not checked.

With these values set, the module will allow private submitted files to
be downloaded by anonymous users. It does not allow access to the submission itself.


