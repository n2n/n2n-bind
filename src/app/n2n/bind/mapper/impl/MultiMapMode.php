<?php

namespace n2n\bind\mapper\impl;

enum MultiMapMode {
	case EVERY_BINDABLE_MUST_EXIST;
	case ANY_BINDABLE_MUST_EXIST;
	case ALWAYS;
}
