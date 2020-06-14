<template>
  <Layout ref="load" class="bonus-apply bg-f8">
    <Navbar :title="navbarTitle" />
    <HeadBanner :src="banner" />
    <ResultState
      :state="applyStateInfo.state"
      :message="applyStateInfo.message"
      v-if="applyStateInfo"
    />
    <div class="cell-group" v-if="isAgent != 1 && pageType != 3">
      <ApplyFormGroup v-if="pageType == 1" :form-list="formList" :params="params" @submit="onApply">
        <template v-if="agentType == 2">
          <CellSelector
            label="代理级别"
            placeholder="请选择代理级别"
            :columns="agentLevelColums"
            @confirm="onAgentLevel"
          />
          <CellAreaPopup
            label="代理区域"
            placeholder="请选择代理区域"
            :info="areaInfo"
            :area-type="areaType"
            @disabled="$Toast('请先选择代理级别！')"
            @confirm="onAreaConfirm"
          />
        </template>
      </ApplyFormGroup>
      <ApplyConditionInfo
        v-else-if="pageType == 2"
        :title="satisfyConditionText"
        :items="conditionInfo"
      />
    </div>
    <Divider :title="protocolText" :content="protocol" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import Divider from "@/components/Divider";
import ResultState from "@/components/ResultState";
import CellAreaPopup from "@/components/CellAreaPopup";
import CellSelector from "@/components/CellSelector";
import ApplyFormGroup from "../commission/component/ApplyFormGroup";
import ApplyConditionInfo from "../commission/component/ApplyConditionInfo";
import {
  GET_APPLYAGENTINFO,
  APPLY_GLOBALAGENT,
  APPLY_AREAAGENT,
  APPLY_TEAMAGENT
} from "@/api/bonus";
import { isEmpty } from "@/utils/util";
export default sfc({
  name: "bonus-apply",
  data() {
    const agentType = this.$route.params.agenttype;
    return {
      /**
       * 申请类型
       * 1==> 全球代理
       * 2==> 区域代理
       * 3==> 团队代理
       */
      agentType,

      banner: null,
      protocol: null,

      isAgent: null,
      condition: {},

      agentLevelColums: [
        {
          text: "省级代理",
          type: 1
        },
        {
          text: "市级代理",
          type: 2
        },
        {
          text: "区级代理",
          type: 3
        }
      ],

      params: {
        real_name: null,
        user_tel: null
      },

      formList: [],

      areaInfo: {},
      areaType: -1 //区域代理级别
    };
  },
  computed: {
    /**
     * 申请情况页面类型
     * 1 ==> 提交表单形式
     * 2 ==> 显示条件形式
     * 3 ==> 拒绝申请形式
     */
    pageType() {
      const agentType = this.agentType;
      const isAgent = this.isAgent;
      const state = this.condition.agent_condition;
      let type = 0;
      if (agentType == 2) {
        type = this.condition.areaagent_status == 2 ? 3 : 1;
      } else {
        if (state == -1 || isAgent == 3) {
          type = 1;
        } else if (state == 1 || state == 2) {
          type = 2;
        }
        // 完善资料 需要提交表单
        if (this.isReplenishInfo) {
          type = 1;
        }
      }
      return type;
    },
    navbarTitle() {
      const { area, global, team } = this.$store.state.member.bonusSetText;
      let title = "";
      if (this.agentType == 1) title = global.apply_global;
      if (this.agentType == 2) title = area.apply_area;
      if (this.agentType == 3) title = team.apply_team;
      if (title) document.title = title;
      return title;
    },
    // 是否完善资料
    isReplenishInfo() {
      return this.$route.hash == "#replenish" ? true : false;
    },
    applyStateInfo() {
      let obj = {};
      let state = this.condition.areaagent_status == 2 ? -2 : this.isAgent;
      if (this.agentType != 2 && this.isReplenishInfo) {
        state = 0;
      }
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
          obj.message = "商城拒绝你成为代理商，请联系客服或重新提交申请。";
          break;
        case -2:
          obj.state = "warn";
          obj.message = "不能在线申请，请联系商城相关人员。";
          break;
      }
      return isEmpty(obj) ? false : obj;
    },
    satisfyConditionText() {
      const condition = this.condition.agent_condition;
      return condition == 1
        ? "满足以下条件自动成为代理商"
        : "满足其中一个条件即可成为代理商";
    },
    conditionInfo() {
      const $this = this;
      const condition = $this.condition.agent_condition;
      const conditionsArr = $this.condition.agent_conditions
        ? $this.condition.agent_conditions.split(",")
        : [];
      let infoArr = [];
      if (condition == 1 || condition == 2) {
        conditionsArr.forEach((e, i) => {
          if (e == "1")
            infoArr.push(
              `${i + 1}：自购订单金额满<span class="text">${
                $this.condition.pay_money
              }</span>元`
            );
          if (e == "2")
            infoArr.push(
              `${i + 1}：下级${
                this.$store.state.member.commissionSetText.distributor_name
              }满<span class="text">${$this.condition.number}</span>人`
            );
          if (e == "3")
            infoArr.push(
              `${i + 1}：一级${
                this.$store.state.member.commissionSetText.distributor_name
              }满<span class="text">${$this.condition.one_number}</span>人`
            );
          if (e == "4")
            infoArr.push(
              `${i + 1}：二级${
                this.$store.state.member.commissionSetText.distributor_name
              }满<span class="text">${$this.condition.two_number}</span>人`
            );
          if (e == "5")
            infoArr.push(
              `${i + 1}：三级${
                this.$store.state.member.commissionSetText.distributor_name
              }满<span class="text">${$this.condition.three_number}</span>人`
            );
          if (e == "6")
            infoArr.push(
              `${i + 1}：下级订单满<span class="text">${
                $this.condition.order_money
              }</span>元`
            );
          if (e == "7")
            infoArr.push(
              `${i +
                1}：购买指定商品&nbsp;&nbsp;<a class="a-link" href="/wap/goods/detail/${
                $this.condition.goods_id
              }">去购买</a>`
            );
        });
      }
      return infoArr;
    },
    isForm() {
      return !isEmpty(this.formList);
    },
    protocolText() {
      const { area, global, team } = this.$store.state.member.bonusSetText;
      let text = "";
      if (this.agentType == 1) text = global.global_agreement;
      if (this.agentType == 2) text = area.area_agreement;
      if (this.agentType == 3) text = team.team_agreement;
      return text + "协议";
    }
  },
  mounted() {
    const $this = this;
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    // 是否分销商
    $this.$store
      .dispatch("isBistributor")
      .then(() => {
        $this.loadData();
      })
      .catch(({ error, callback }) => {
        if (error) {
          this.$refs.load.fail();
        }
        if (callback) {
          this.$refs.load.result();
          this.$Toast(
            "请先成为" +
              this.$store.state.member.commissionSetText.distributor_name +
              "！"
          );
          this.$router.replace("/member/centre");
        }
      });
  },
  methods: {
    loadData() {
      const $this = this;
      GET_APPLYAGENTINFO($this.agentType)
        .then(({ data }) => {
          if ($this.agentType == 1) {
            // 申请全球代理
            $this.banner = data.global_bonus_agreement.logo;
            $this.protocol = data.global_bonus_agreement.content;
            $this.condition = data.global_bonus;
            $this.condition.agent_condition =
              data.global_bonus.globalagent_condition;
            $this.condition.agent_conditions =
              data.global_bonus.globalagent_conditions;
            $this.isAgent = data.is_global_agent;
            // 股东申请自定义表单
            $this.formList = !isEmpty(data.global_bonus_agreement.customform)
              ? data.global_bonus_agreement.customform
              : [];
          } else if ($this.agentType == 2) {
            // 申请区域代理
            $this.banner = data.area_bonus_agreement.logo;
            $this.protocol = data.area_bonus_agreement.content;
            $this.condition = data.area_bonus;
            $this.isAgent = data.is_area_agent;
            // 区域代理申请自定义表单
            $this.formList = !isEmpty(data.area_bonus_agreement.customform)
              ? data.area_bonus_agreement.customform
              : [];
          } else if ($this.agentType == 3) {
            // 申请团队代理
            $this.banner = data.team_bonus_agreement.logo;
            $this.protocol = data.team_bonus_agreement.content;
            $this.condition = data.team_bonus;
            $this.condition.agent_condition =
              data.team_bonus.teamagent_condition;
            $this.condition.agent_conditions =
              data.team_bonus.teamagent_conditions;
            $this.isAgent = data.is_team_agent;

            // 团队申请自定义表单
            $this.formList = !isEmpty(data.team_bonus_agreement.customform)
              ? data.team_bonus_agreement.customform
              : [];
          }
          $this.params.real_name = data.real_name;
          $this.params.user_tel = data.user_tel;

          if (!$this.isReplenishInfo && $this.isAgent == 2) {
            $this.$refs.load.result();
            $this.$router.replace("/bonus/centre");
          } else {
            $this.$refs.load.success();
          }
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onAgentLevel({ type }) {
      this.params.area_id = type;
      this.areaType = type;
      this.params.province_id = "";
      this.params.city_id = "";
      this.params.district_id = "";
      this.areaInfo = {};
    },
    onAreaConfirm(data) {
      this.areaInfo = data;
    },
    onApply(params) {
      const $this = this;
      if (!$this.isForm && $this.agentType == 2) {
        if (!$this.params.area_id) {
          $this.$Toast("请选择代理等级！");
          return false;
        }
        if (isEmpty($this.areaInfo)) {
          $this.$Toast("请选择代理区域！");
          return false;
        }
        if ($this.params.area_id == 1) {
          $this.params.province_id = $this.areaInfo.id[0];
        }
        if ($this.params.area_id == 2) {
          $this.params.province_id = $this.areaInfo.id[0];
          $this.params.city_id = $this.areaInfo.id[1];
        }
        if ($this.params.area_id == 3) {
          $this.params.province_id = $this.areaInfo.id[0];
          $this.params.city_id = $this.areaInfo.id[1];
          $this.params.district_id = $this.areaInfo.id[2];
        }
        params = $this.params;
      }
      // console.log(params);
      // return;
      if ($this.agentType == 1) {
        // 申请全球代理
        APPLY_GLOBALAGENT(params).then(({ message }) => {
          $this.$Toast.success("提交成功");
          setTimeout(() => {
            $this.$router.replace("/member/centre");
          }, 200);
        });
      } else if ($this.agentType == 2) {
        // 申请区域代理
        APPLY_AREAAGENT(params).then(({ message }) => {
          $this.$Toast.success("提交成功");
          setTimeout(() => {
            $this.$router.replace("/member/centre");
          }, 200);
        });
      } else if ($this.agentType == 3) {
        // 申请团队代理
        APPLY_TEAMAGENT(params).then(({ message }) => {
          $this.$Toast.success("提交成功");
          setTimeout(() => {
            $this.$router.replace("/member/centre");
          }, 200);
        });
      }
    }
  },
  components: {
    HeadBanner,
    Divider,
    CellAreaPopup,
    CellSelector,
    ResultState,

    ApplyFormGroup,
    ApplyConditionInfo
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
