<#1>
create table pwrtla_tokens (                       -- OAUTH Token store
    token_id     varchar(255) PRIMARY KEY,         -- shared id (token in Bearer)
    token_type   varchar(255) NOT NULL,            -- type in [Bearer, Client, MAC]
    token_key    varchar(255),                     -- private key (only shared once)
    client_id    varchar(255),                     -- client identifier, e.g., device UUID
    user_id      INT,                              -- internal user id if type = Bearer or MAC
    domain       varchar(255),                     -- reverse identifier of the app
    extra        TEXT                              -- extra token settings
);

<#2>
create table pwrtla_authpins (                     -- used for one-time authentification pins
    login        varchar(255) NOT NULL UNIQUE,     -- user name
    pinhash      varchar(255) NOT NULL,            -- sha1 hash of the generated pin code
    created      INT NOT NULL                      -- timestamps for invalidating old pins
);


<#3>
create table pwrtla_usertokens(                    -- used for public profile to obscure internal ids
    user_id      INT          PRIMARY KEY,         -- the internal user id
    user_token   varchar(255) NOT NULL UNIQUE      -- the external key for linking
);

<#4>
create table pwrtla_xapistatements (               -- XAPI LRS
    uuid         VARCHAR(255) PRIMARY KEY,         -- statement id
    statement    TEXT NOT NULL,                    -- the actual statement JSON
    stored       INT NOT NULL,                     -- the timestamp of arrival
    voided       varchar(255),                     -- a reference to the voiding statement
    tsyear       INT NOT NULL,                     -- stream partitioning
    tsmonth      INT NOT NULL,
    tsday        INT NOT NULL,
    tshour       INT NOT NULL,                     -- clustering
    tsminute     INT NOT NULL,
    verb_id      varchar(255),
    agent_id     varchar(255),
    object_id    varchar(255),
    score        INT,
    duration     INT,                              -- stats
    user_id      INT NOT NULL,                     -- the internal user id of the actor
    registration varchar(255)                      -- registration context (if set)
);

<#5>
create table pwrtla_xapicontexts (   -- for fast context retrieval
    uuid         varchar(255) not null,            -- reference to a LRS statement.id
    context_type varchar(255) not null,            -- the name of the context (e.g., "parent")
    context_id   varchar(255) not null             -- the IRI/UUID value of the context
);

<#6>
create table pwrtla_xapidocuments (               -- XAPI Document Repository
    uuid           VARCHAR(255) PRIMARY KEY,      -- document id
    document     TEXT         NOT NULL,           -- the document JSON
    doctype      varchar(255) NOT NULL,           -- document type derieved from the service signature
    agent        TEXT         NOT NULL,           -- the agent/actor JSON string
    agent_id      varchar(255) NOT NULL,           -- agent id (for faster retrieval)
    statement_id  varchar(255),                    -- reference to a LRS statement.id
    stored       INT NOT NULL,                     -- implement since
    object_id    varchar(255),
    verb_id      varchar(255),
    registration varchar(255)
);

<?php
// done
?>