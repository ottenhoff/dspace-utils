<?php
$newDb = trim($argv[1]);
$new = pg_connect ($newDb);

if (!$new) {
  exit ("Could not make connection to new database server\n");
}

$sql = <<<EOF
SELECT setval('bitstreamformatregistry_seq', max(bitstream_format_id)) FROM bitstreamformatregistry;
SELECT setval('fileextension_seq', max(file_extension_id)) FROM fileextension;
SELECT setval('bitstream_seq', max(bitstream_id)) FROM bitstream;
SELECT setval('eperson_seq', max(eperson_id)) FROM eperson;
SELECT setval('epersongroup_seq', max(eperson_group_id)) FROM epersongroup;
SELECT setval('group2group_seq', max(id)) FROM group2group;
SELECT setval('group2groupcache_seq', max(id)) FROM group2groupcache;
SELECT setval('item_seq', max(item_id)) FROM item;
SELECT setval('bundle_seq', max(bundle_id)) FROM bundle;
SELECT setval('item2bundle_seq', max(id)) FROM item2bundle;
SELECT setval('bundle2bitstream_seq', max(id)) FROM bundle2bitstream;
SELECT setval('dcvalue_seq', max(dc_value_id)) FROM dcvalue;
SELECT setval('community_seq', max(community_id)) FROM community;
SELECT setval('community2community_seq', max(id)) FROM community2community;
SELECT setval('collection_seq', max(collection_id)) FROM collection;
SELECT setval('community2collection_seq', max(id)) FROM community2collection;
SELECT setval('collection2item_seq', max(id)) FROM collection2item;
SELECT setval('resourcepolicy_seq', max(policy_id)) FROM resourcepolicy;
SELECT setval('epersongroup2eperson_seq', max(id)) FROM epersongroup2eperson;
SELECT setval('workspaceitem_seq', max(workspace_item_id)) FROM workspaceitem;
SELECT setval('workflowitem_seq', max(workflow_id)) FROM workflowitem;
SELECT setval('tasklistitem_seq', max(tasklist_id)) FROM tasklistitem;
SELECT setval('registrationdata_seq', max(registrationdata_id)) FROM registrationdata;
SELECT setval('subscription_seq', max(subscription_id)) FROM subscription;
SELECT setval('communities2item_seq', max(id)) FROM communities2item;
SELECT setval('epersongroup2workspaceitem_seq', max(id)) FROM epersongroup2workspaceitem;
SELECT setval('metadatafieldregistry_seq', max(metadata_field_id)) FROM metadatafieldregistry;
SELECT setval('metadatavalue_seq', max(metadata_value_id)) FROM metadatavalue;
SELECT setval('metadataschemaregistry_seq', max(metadata_schema_id)) FROM metadataschemaregistry;
SELECT setval('harvested_collection_seq', max(id)) FROM harvested_collection;
SELECT setval('harvested_item_seq', max(id)) FROM harvested_item;
SELECT setval('handle_seq',
              CAST (
                    max(
                        to_number(regexp_replace(handle, '.*/', ''), '999999999999')
                       )
                    AS BIGINT)
             )
    FROM handle
    WHERE handle SIMILAR TO '%/[0123456789]*';
EOF;

$ret = pg_query ($new, $sql);
var_dump($ret);
