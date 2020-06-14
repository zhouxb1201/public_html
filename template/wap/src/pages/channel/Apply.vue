<template>
  <Layout ref="load" class="channel-apply bg-f8">
    <Navbar />
    <HeadBanner :src="banner" />
    <ResultState
      :state="applyStateInfo.state"
      :message="applyStateInfo.message"
      v-if="applyStateInfo"
    />
    <van-cell-group class="apply-condition" v-if="isChannel == -2">
      <van-cell :value="satisfyConditionText" />
      <van-cell>
        <div v-for="(item,index) in condition" :key="index">
          <span>
            {{item.text}}
            <a
              class="a-link"
              :href="'/wap/goods/detail/'+item.goods_id"
              v-if="item.goods_id"
            >去购买</a>
          </span>
        </div>
      </van-cell>
    </van-cell-group>
    <ApplyFormGroup
      v-if="isChannel == -1 || isChannel == 0"
      :form-list="formList"
      :params="params"
      @submit="onApply"
    />
    <Divider title="申请协议" :content="protocol" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import Divider from "@/components/Divider";
import ResultState from "@/components/ResultState";
import { GET_APPLYINFO, APPLY_CHANNEL } from "@/api/channel";
import { isEmpty } from "@/utils/util";
import { validMobile, validUsername } from "@/utils/validator";
import ApplyFormGroup from "../commission/component/ApplyFormGroup";
export default sfc({
  name: "channel-apply",
  data() {
    return {
      banner: null,
      protocol: null,
      info: "",

      params: {
        user_tel: null,
        real_name: null
      },
      user_tel: null,
      condition: {},
      isChannel: null,
      state: null,

      formList: [],

      checked: false
    };
  },
  mounted() {
    const $this = this;
    GET_APPLYINFO()
      .then(({ data }) => {
        if (data.is_checked == 2) {
          $this.$refs.load.result();
          $this.$router.replace("/channel/centre");
        } else {
          $this.params.real_name = data.real_name;
          $this.params.user_tel = data.user_tel;
          $this.user_tel = data.user_tel;
          $this.banner = data.channel_agreement
            ? data.channel_agreement.logo
            : "";
          $this.protocol = data.channel_agreement
            ? data.channel_agreement.condition
            : "";
          $this.condition = data.condition;
          $this.state = data.channel_condition;
          $this.isChannel = data.is_checked;
          $this.formList = !isEmpty(data.customform)
            ? data.customform.channel
            : [];
          $this.$refs.load.success();
        }
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  computed: {
    applyStateInfo() {
      const state = this.isChannel;
      let obj = {};
      switch (state) {
        case 1:
          obj.state = "wait";
          obj.message = "申请提交成功，请耐心等待商城审核。";
          break;
        case 2:
          obj.state = "success";
          obj.message = "已通过审核。";
          break;
        case 3:
          obj.state = "info";
          obj.message = "满足条件，请完善资料。";
          break;
        case -1:
          obj.state = "error";
          obj.message = "商城拒绝你成为渠道商，请联系客服或重新提交申请。";
          break;
      }
      return isEmpty(obj) ? false : obj;
    },
    satisfyConditionText() {
      return this.state == "all"
        ? "满足以下条件自动成为渠道商："
        : "满足其中一个条件即可成为渠道商";
    },
    // 满足条件状态
    satisfyConditionStatus() {
      return this.condition.to_channel_status &&
        this.condition.to_channel_status == 1
        ? true
        : false;
    }
  },
  methods: {
    onApply(params) {
      const $this = this;
      // console.log(params);
      // return false;
      APPLY_CHANNEL(params).then(({ message }) => {
        $this.$Toast.success(message);
        setTimeout(() => {
          $this.$router.replace("/member/centre");
        }, 1000);
      });
    }
  },
  components: {
    HeadBanner,
    Divider,
    ResultState,
    ApplyFormGroup
  }
});
</script>
<style scoped>
.apply-condition {
  background: #fff;
  margin: 10px 0;
}

.apply-condition .apply-btn {
  margin-bottom: 10px;
}

.apply-condition .apply-checkbox {
  padding-bottom: 10px;
}

.condition-item >>> .text {
  padding: 0 4px;
  color: #ff454e;
}
</style>
