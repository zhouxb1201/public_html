<template>
  <van-popup v-model="show" :close-on-click-overlay="false" class="popup-bind-mobile">
    <van-cell-group class="cell-group">
      <van-cell>
        <div class="title">{{title}}</div>
        <van-icon name="close" size="16px" class="btn-close" @click="onClose" />
      </van-cell>
      <van-field
        label="手机号码"
        type="number"
        maxlength="11"
        placeholder="请输入您的手机号码"
        v-model="form.mobile"
        @blur="onBlur"
      />
      <CellMsgCodeGroup
        v-model="form.verification_code"
        :mobile="form.mobile"
        type="bind_mobile"
        :show-left-icon="false"
        ref="CellMsgCodeGroup"
        @send-success="onSendSuccess"
      />

      <template v-if="isNewUser">
        <van-field label="设置密码" v-model="password" type="password" placeholder="请输入新密码" />
        <van-field label="确认密码" v-model="check_password" type="password" placeholder="请输入确认新密码" />
      </template>

      <van-cell :border="false" value-class="text-agree" v-if="regRule">
        授权即代表同意
        <PopupProtocol />
      </van-cell>
      <van-cell>
        <van-button
          size="normal"
          round
          type="danger"
          block
          :loading="isLoading"
          @click="onBind"
        >同意协议并绑定</van-button>
      </van-cell>
    </van-cell-group>
  </van-popup>
</template>

<script>
import store from "@/store";
import {
  validMobile,
  validMsgcode,
  validPassword,
  validCheckPassword
} from "@/utils/validator";
import { Icon, Cell, CellGroup, Button, Popup, Field } from "vant";
import PopupProtocol from "../PopupProtocol";
import { IS_HASMOBILE } from "@/api/user";
import { focusout } from "@/mixins";
import CellMsgCodeGroup from "@/components/CellMsgCodeGroup";

export default {
  data() {
    return {
      show: false,
      isLoading: false,
      isNewUser: false, // 是否为新用户
      form: {
        mobile: "",
        verification_code: ""
      },
      password: "",
      check_password: ""
    };
  },
  mixins: [focusout],
  computed: {
    title() {
      return this.isNewUser ? "绑定手机号" : "关联手机号";
    },
    regRule() {
      if (store.getters.config != undefined) {
        return store.getters.config.reg_rule;
      }
    }
  },
  methods: {
    onBlur() {
      this.validNewUser();
    },
    // 验证是否为新用户
    validNewUser() {
      return new Promise((resolve, reject) => {
        if (validMobile(this.form.mobile)) {
          let port =
            store.state.isWeixin && store.getters.config.is_wchat ? 1 : 3;
          IS_HASMOBILE(this.form.mobile, port).then(({ code }) => {
            this.isNewUser = code ? false : true;
            resolve();
          });
        }
      });
    },
    onBind() {
      let form = this.form;
      this.validNewUser().then(() => {
        if (
          !validMobile(form.mobile) ||
          !validMsgcode(form.verification_code)
        ) {
          return false;
        }
        if (this.isNewUser) {
          if (!validPassword(this.password)) {
            return false;
          }
          form.password = this.password;
          if (!validCheckPassword(form.password, this.check_password)) {
            return false;
          }
        } else {
          delete form.password;
        }
        // console.log(form);
        // return;
        this.isLoading = true;
        store
          .dispatch("bindAccount", form)
          .then(res => {
            this.isLoading = false;
            this.onClose();
            location.reload();
          })
          .catch(() => {
            this.isLoading = false;
          });
      });
    },
    // 发送验证码完成
    onSendSuccess({ isHasMobile }) {
      this.isNewUser = isHasMobile == 1 ? false : true;
    },
    onClose() {
      this.close();
      this.isLoading = false;
      this.isNewUser = false;
      this.form = {
        mobile: "",
        verification_code: ""
      };
      this.password = "";
      this.check_password = "";
      this.$refs.CellMsgCodeGroup.endTimer();
    }
  },
  components: {
    [Icon.name]: Icon,
    [Cell.name]: Cell,
    [Field.name]: Field,
    [Button.name]: Button,
    [Popup.name]: Popup,
    [CellGroup.name]: CellGroup,
    PopupProtocol,
    CellMsgCodeGroup
  }
};
</script>

<style scoped>
.popup-bind-mobile {
  width: 80%;
  border-radius: 10px;
}

.title {
  font-weight: 800;
  text-align: center;
  position: relative;
}

.text-agree {
  color: #909399;
  font-size: 12px;
  display: flex;
}

.btn-close {
  position: absolute;
  right: 0;
  top: 0;
}
</style>
