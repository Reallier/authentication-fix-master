/**
 * 综合验证状态
 */
export interface AuthStatus {
	success: boolean;
	data: AuthStatusData
}

/**
 * 获取实人认证后的信息
 */
interface AuthStatusCertInfo {
	name: string;
	id: string;
}

/**
 * 获取手机验证后的信息
 */
interface AuthStatusPhoneInfo {
	phone: string;
}

export interface AuthStatusData {
	certStatus: boolean;
	phoneStatus: boolean;
	companyName: string;
	certInfo?: AuthStatusCertInfo;
	phoneInfo?: AuthStatusPhoneInfo;
}

/**
 * 手机验证状态
 */
export interface PhoneStatus {
	success: boolean;
	data: PhoneStatusData
}

export interface PhoneStatusData {
	phoneStatus: boolean;
	hasCodeSend?: boolean;
}