<?php

namespace n2n\bind\mapper\impl;

enum MultiMapMode {
	case EVERY_BINDABLE_MUST_BE_PRESENT;
	case ANY_BINDABLE_MUST_BE_PRESENT;
	case ALWAYS;
}
